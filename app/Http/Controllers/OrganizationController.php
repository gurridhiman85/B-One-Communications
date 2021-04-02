<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseConnection;
use App\Helpers\Helper;
use App\Library\Ajax;
use App\Model\Announcementorganizationmapping;
use App\Model\Departmentorganizationmapping;
use App\Model\Extensionorganizationmapping;
use App\Model\Organization;
use App\Model\Phoneorganizationmapping;
use App\Rules\DepartmentRange;
use http\Client\Curl\User;
use Illuminate\Http\Request;
use DB;
use Config;
use Auth;
use Crypt;
use Validator;
use Illuminate\Support\Facades\Artisan;
use \Illuminate\Support\Facades\View as View;


class OrganizationController extends Controller
{

    public function index(){
        return view('organizations.index',[
            'organization_name' => Auth::user()->IsOrganizationAssigned ? Auth::user()->organization->organization_name : '(Not Assigned)'
        ]);
    }

    public function getOrganizations(Request $request,Ajax $ajax){
        $tabid = $request->input('tabid');
        $sort = $request->input('sort') ? $request->input('sort') : '';
        $dir = $request->input('dir') ? $request->input('dir') : '';
        $page = $request->input('page',1);
        $records_per_page = 5; //config('constant.record_per_page');
        $sort = ($sort == "") ? "Order By CampaignId DESC" : $sort == 'CampaignId' ? "Order By CampaignId DESC" : "Order By $sort $dir";
        $rType = $request->input('rtype','');
        $filters = $request->input('filters',[]);
        $position = ($page-1) * $records_per_page;

        try {
            $oOrganizations = \App\Model\Organization::skip($position)
                ->take($records_per_page)->orderBy('organization_name')
                ->get();

            $tTotalOrganizations = \App\Model\Organization::count();

            $tabName = 'Organizations';
            if($rType == 'pagination'){
                $html = View::make('organizations.tabs.list.table',['oOrganizations' => $oOrganizations,'tab' => $tabName])->render();
            }else{
                $html = View::make('organizations.tabs.list.index',['oOrganizations' => $oOrganizations,'tab' => $tabName])->render();
            }
            $paginationhtml = View::make('organizations.tabs.list.pagination-html',[
                'total_records' => $tTotalOrganizations,
                'records' => $oOrganizations,
                'position' => $position,
                'records_per_page' => $records_per_page,
                'page' => $page,
                'tab' => $tabName
            ])->render();

            return $ajax->success()
                ->appendParam('Organizations', $oOrganizations)
                ->appendParam('html',$html)
                ->appendParam('paginationHtml',$paginationhtml)
                ->jscallback('load_ajax_tab')
                ->response();
        } catch(\Exception $ex){
            return $ajax->fail()
                ->message($ex->getMessage())
                ->response();
        }
    }

    public function editOrganization($enc_organization_id,Ajax $ajax){
        $organization_id = Crypt::decrypt($enc_organization_id);
        if($organization_id != '0'){
            $oOrganization = Organization::where('id',$organization_id)->first();
            /*if(!$dDepartment){
                return $ajax->fail()
                    ->message('Department not found')
                    ->jscallback()
                    ->response();
            }*/
            $cConnection = DatabaseConnection::setConnection($oOrganization->server);

            $organizationExtensions = Extensionorganizationmapping::where('organization_id',$organization_id)
                ->pluck('extension')
                ->toArray();

            $eExtensionLists = $cConnection->table('users')
                ->whereIn('extension',$organizationExtensions)
                ->orderBy('extension')
                ->get(['extension','name']);


            $organizationDepartments = Departmentorganizationmapping::where('organization_id',$organization_id)
                ->pluck('department_id')
                ->toArray();
            $dDepartmentLists = $cConnection->table('ringgroups')
                ->whereIn('grpnum',$organizationDepartments)
                ->orderBy('grpnum')
                ->get(['grpnum','description']);

            $organizationPhoneNumbers = Phoneorganizationmapping::where('organization_id',$organization_id)
                ->pluck('phone_number')
                ->toArray();
            $pPhoneNumbersLists = $cConnection->table('incoming')
                ->whereIn('extension',$organizationPhoneNumbers)
                ->orderBy('extension')
                ->get(['extension','description']);

            $organizationUsersLists = \App\User::where('organization_id',$organization_id)
                ->get();

            $organizationAnnouncements = Announcementorganizationmapping::where('organization_id',$organization_id)
                ->pluck('announcement_id')
                ->toArray();
            $aAnnouncementsLists = $cConnection->table('announcement')
                ->whereIn('announcement_id',$organizationAnnouncements)
                ->orderBy('announcement_id')
                ->get(['announcement_id','description']);

            return view('organizations.form.add',[
                'title' => 'Edit Organization',
                'organization_name' => Auth::user()->IsOrganizationAssigned ? Auth::user()->organization->organization_name : '(Not Assigned)',
                'organization' => $oOrganization,
                'eExtensionLists' => $eExtensionLists,
                'dDepartmentLists' => $dDepartmentLists,
                'pPhoneNumbersLists' => $pPhoneNumbersLists,
                'aAnnouncementsLists' => $aAnnouncementsLists,
                'uUsersLists' => $organizationUsersLists,
                'orgExtensions' => count($organizationExtensions) > 0 ? $organizationExtensions : [],
                'orgDepartments' => count($organizationDepartments) > 0 ? $organizationDepartments : [],
                'orgPhoneNumbers' => count($organizationPhoneNumbers) > 0 ? $organizationPhoneNumbers : [],
                'orgAnnouncements' => count($organizationAnnouncements) > 0 ? $organizationAnnouncements : []
            ]);
        }else{
            return view('organizations.form.add',[
                'title' => 'Add Organization',
                'organization_name' => Auth::user()->IsOrganizationAssigned ? Auth::user()->organization->organization_name : '(Not Assigned)',
            ]);
        }
    }

    /**
     * @param Request $request
     * @param Ajax $ajax
     * @return mixed
     */
    public function unassignedExtensions($enc_org_id,Request $request,Ajax $ajax){
        $organization_id = Crypt::decrypt($enc_org_id);
        $organization = Organization::find($organization_id);
        if(!$organization){
            return $ajax->fail()
                ->message('Organization not found')
                ->jscallback()
                ->response();
        }

        $aAssignedExtensions = Extensionorganizationmapping::where('server_id',$organization->server_ID)->pluck('extension')->toArray();

        $cConnection = DatabaseConnection::setConnection($organization->server);
        $eExtensions = $cConnection->table('users')
            ->whereNotIn('extension',$aAssignedExtensions)
            ->orderBy('extension')
            ->get(['extension','name']);

        $title = 'Choose Extensions';
        $content = View::make('organizations.popup.extensions',[
            'eExtensions' => $eExtensions,
            'lists' => !empty($exts) ? explode(',',$exts) : []
        ])->render();

        $size = 'modal-md';
        $sdata = [
            'content' => $content
        ];

        if (isset($title)) {
            $sdata['title'] = $title;
        }
        if (isset($size)) {
            $sdata['size'] = $size;
        }

        $view = View::make('layouts.modal-popup-layout', $sdata);
        $html = $view->render();

        return $ajax->success()
            ->appendParam('html', $html)
            ->jscallback('loadModalLayout')
            ->response();

    }

    public function unassignedDepartments($enc_org_id,Request $request,Ajax $ajax){
        $organization_id = Crypt::decrypt($enc_org_id);
        $organization = Organization::find($organization_id);
        if(!$organization){
            return $ajax->fail()
                ->message('Organization not found')
                ->jscallback()
                ->response();
        }

        $aAssignedDepartments = Departmentorganizationmapping::where('server_id',$organization->server_ID)->pluck('department_id')->toArray();

        $cConnection = DatabaseConnection::setConnection($organization->server);
        $dDepartments = $cConnection->table('ringgroups')
            ->whereNotIn('grpnum',$aAssignedDepartments)
            ->orderBy('grpnum')
            ->get(['grpnum','description']);

        $title = 'Choose Department';
        $content = View::make('organizations.popup.departments',[
            'dDepartments' => $dDepartments,
            'lists' => !empty($exts) ? explode(',',$exts) : []
        ])->render();

        $size = 'modal-md';
        $sdata = [
            'content' => $content
        ];

        if (isset($title)) {
            $sdata['title'] = $title;
        }
        if (isset($size)) {
            $sdata['size'] = $size;
        }

        $view = View::make('layouts.modal-popup-layout', $sdata);
        $html = $view->render();

        return $ajax->success()
            ->appendParam('html', $html)
            ->jscallback('loadModalLayout')
            ->response();

    }

    public function unassignedPhoneNumbers($enc_org_id,Request $request,Ajax $ajax){
        $organization_id = Crypt::decrypt($enc_org_id);
        $organization = Organization::find($organization_id);
        if(!$organization){
            return $ajax->fail()
                ->message('Organization not found')
                ->jscallback()
                ->response();
        }

        $aAssignedPhoneNumbers = Phoneorganizationmapping::where('server_id',$organization->server_ID)->pluck('phone_number')->toArray();

        $cConnection = DatabaseConnection::setConnection($organization->server);
        $pPhoneNumbers = $cConnection->table('incoming')
            ->whereNotIn('extension',$aAssignedPhoneNumbers)
            ->orderBy('extension')
            ->get(['extension','description']);

        $title = 'Choose Phone Number';
        $content = View::make('organizations.popup.phonenumbers',[
            'pPhoneNumbers' => $pPhoneNumbers
        ])->render();

        $size = 'modal-md';
        $sdata = [
            'content' => $content
        ];

        if (isset($title)) {
            $sdata['title'] = $title;
        }
        if (isset($size)) {
            $sdata['size'] = $size;
        }

        $view = View::make('layouts.modal-popup-layout', $sdata);
        $html = $view->render();

        return $ajax->success()
            ->appendParam('html', $html)
            ->jscallback('loadModalLayout')
            ->response();

    }

    public function unassignedAnnouncements($enc_org_id,Request $request,Ajax $ajax){
        $organization_id = Crypt::decrypt($enc_org_id);
        $organization = Organization::find($organization_id);
        if(!$organization){
            return $ajax->fail()
                ->message('Organization not found')
                ->jscallback()
                ->response();
        }

        $aAssignedAnnouncements = Announcementorganizationmapping::where('server_id',$organization->server_ID)->pluck('announcement_id')->toArray();
        $cConnection = DatabaseConnection::setConnection($organization->server);
        $aAnnouncements = $cConnection->table('announcement')
            ->whereNotIn('announcement_id',$aAssignedAnnouncements)
            ->orderBy('announcement_id')
            ->get(['announcement_id','description']);

        $title = 'Choose Announcement';
        $content = View::make('organizations.popup.announcements',[
            'aAnnouncements' => $aAnnouncements
        ])->render();

        $size = 'modal-md';
        $sdata = [
            'content' => $content
        ];

        if (isset($title)) {
            $sdata['title'] = $title;
        }
        if (isset($size)) {
            $sdata['size'] = $size;
        }

        $view = View::make('layouts.modal-popup-layout', $sdata);
        $html = $view->render();

        return $ajax->success()
            ->appendParam('html', $html)
            ->jscallback('loadModalLayout')
            ->response();

    }

    public function allUsers(Ajax $ajax){
        $uUsers = \App\User::whereNull('organization_id')->where('is_active',1)->get();

        $title = 'Choose User';
        $content = View::make('organizations.popup.users',[
            'uUsers' => $uUsers
        ])->render();

        $size = 'modal-md';
        $sdata = [
            'content' => $content
        ];

        if (isset($title)) {
            $sdata['title'] = $title;
        }
        if (isset($size)) {
            $sdata['size'] = $size;
        }

        $view = View::make('layouts.modal-popup-layout', $sdata);
        $html = $view->render();

        return $ajax->success()
            ->appendParam('html', $html)
            ->jscallback('loadModalLayout')
            ->response();
    }

    public function unassignedUpdate(Request $request,Ajax $ajax){
        $type = $request->input('type','');
        $grplist = $request->input('grplist','');
        return $ajax->success()
            ->appendParam('grplist', $grplist)
            ->jscallback('ajax_org_'.$type.'_list')
            ->response();
    }

    public function updateOrganization(Request $request,Ajax $ajax){
        $organization_id = Crypt::decrypt($request->input('enc_id'));
        $extensions = $request->input('extensions','');
        $departments = $request->input('departments','');
        $phonenumbers = $request->input('phonenumbers','');
        $announcements = $request->input('announcements','');
        $users = $request->input('users','');
        $rules = [
            'organization_name' => 'required|min:2|max:25',
            'server_ID' => 'required'
        ];

        $messages = [
            'organization_name.required' => 'Organization name is required',
            'organization_name.min' => 'Organization name is minimum 2 characters',
            'organization_name.max' => 'Organization name is maximum 25 characters',
            'server_ID.required' => 'Server is required'
        ];

        if($organization_id != 0){
            $organization = Organization::find($organization_id);
            if(!$organization){
                return $ajax->fail()
                    ->message('Organization not found')
                    ->jscallback()
                    ->response();
            }
        }else{
            $organization = new Organization();
            $rules['department_range_from'] = 'required|numeric|min:100|max:100000';
            $rules['department_range_to'] = 'required|numeric|min:100|max:100000';
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if($organization_id == 0){

            $validator->after(function ($validator) use ($request) {
                if(
                    !empty(trim($request->input('department_range_from'))) &&
                    !empty(trim($request->input('department_range_to'))) &&
                    (trim($request->input('department_range_from')) > trim($request->input('department_range_to')) )
                ){
                    $validator->errors()->add('department_range_to', 'Department range can\'t be less then department range from' );
                }else{
                    $range = new DepartmentRange();
                    $resultrange = $range->checkRange(trim($request->input('department_range_from')),trim($request->input('department_range_to')));
                    if(!$resultrange) {
                        $validator->errors()->add('department_range_from', 'Department range from is already in used');
                        $validator->errors()->add('department_range_to', 'Department range to is already in used');
                    }
                }
            });
        }

        if ($validator->fails()) {
            return $ajax->fail()
                ->form_errors($validator->errors())
                ->jscallback()
                ->response();
        }

        $organization->organization_name = trim($request->input('organization_name'));
        $ajax->message(' Organization updated successfully');
        if($organization_id == 0) {
            $organization->server_ID = trim($request->input('server_ID'));
            $organization->department_range = trim($request->input('department_range_from')).'-'.trim($request->input('department_range_to'));
            $ajax->message(' Organization added successfully');
        }

        $organization->save();

        //Extension Mapping
        Extensionorganizationmapping::where('organization_id',$organization->id)->delete();
        if(!empty($extensions)){
            if(strpos($extensions, ',') !== false){
                $extsArr = explode(',',$extensions);
            } else{
                $extsArr = [$extensions];
            }
            $data = [];
            foreach ($extsArr as $ext){
                array_push($data,[
                    'extension' => $ext,
                    'organization_id' => $organization->id,
                    'server_id' => $organization->server_ID,
                    'is_active' => 1
                ]);
            }
            Extensionorganizationmapping::insert($data);
        }

        // Department mapping
        Departmentorganizationmapping::where('organization_id',$organization->id)->delete();
        if(!empty($departments)){
            if(strpos($departments, ',') !== false){
                $departArr = explode(',',$departments);
            } else{
                $departArr = [$departments];
            }
            $data = [];

            foreach ($departArr as $depart){
                array_push($data,[
                    'department_id' => $depart,
                    'organization_id' => $organization->id,
                    'server_id' => $organization->server_ID,
                    'is_active' => 1
                ]);
            }
            Departmentorganizationmapping::insert($data);
        }

        // Phone number mapping
        Phoneorganizationmapping::where('organization_id',$organization->id)->delete();
        if(!empty($phonenumbers)){
            if(strpos($phonenumbers, ',') !== false){
                $phonenArr = explode(',',$phonenumbers);
            } else{
                $phonenArr = [$phonenumbers];
            }
            $data = [];

            foreach ($phonenArr as $phonen){
                array_push($data,[
                    'phone_number' => $phonen,
                    'organization_id' => $organization->id,
                    'server_id' => $organization->server_ID,
                    'is_active' => 1
                ]);
            }
            Phoneorganizationmapping::insert($data);
        }

        // Announcements mapping
        Announcementorganizationmapping::where('organization_id',$organization->id)->delete();
        if(!empty($announcements)){
            if(strpos($announcements, ',') !== false){
                $announArr = explode(',',$announcements);
            } else{
                $announArr = [$announcements];
            }
            $data = [];

            foreach ($announArr as $announ){
                array_push($data,[
                    'announcement_id' => $announ,
                    'organization_id' => $organization->id,
                    'server_id' => $organization->server_ID,
                    'is_active' => 1
                ]);
            }
            Announcementorganizationmapping::insert($data);
        }

        // Users mapping
        \App\User::where('organization_id',$organization->id)
            ->update([
                'organization_id' => null
            ]);

        if(!empty($users)){
            if(strpos($users, ',') !== false){
                $userIdsArr = explode(',',$users);
            } else{
                $userIdsArr = [$users];
            }

            \App\User::whereIn('id',$userIdsArr)
                ->update([
                    'organization_id' => $organization->id
                ]);
        }

        return $ajax->success()
            ->jscallback()
            ->appendParam('redirect',true)
            ->redirectTo(route('organization.index'))
            ->response();
    }

    public function changeStatus($enc_uid,Request $request,Ajax $ajax){
        /*if(!Auth::user()->IsSuperAdmin){
            return view('layouts.error_pages.404');
        }*/

        $uid = Crypt::decrypt($enc_uid);
        $organization = Organization::where('id',$uid)->first();
        $sStatusToggle = $organization->is_active == 1 ? 0 : 1;
        if(!$organization){
            return $ajax->fail()
                ->message('Organization not found !')
                ->jscallback()
                ->response();
        }
        Organization::where('id',$uid)->update(['is_active' => $sStatusToggle]);
        $statusText  = $organization->is_active == 1 ? 'InActive' : 'Active';
        return $ajax->success()
            ->message('Organization '.$statusText)
            ->appendParam('is_active', $sStatusToggle)
            ->jscallback('ajax_status_toggle')
            ->response();
    }

    public function changeAccess($enc_organization_id, Ajax $ajax){
        if(Helper::CheckPermission(null,'organization','edit') || Auth::user()->IsAdmin){
            $organization_id = Crypt::decrypt($enc_organization_id);
            $organization = Organization::find($organization_id);
            if(!$organization){
                return $ajax->fail()
                    ->message('Organization not found')
                    ->jscallback()
                    ->response();
            }
            \App\User::where('id',Auth::id())->update([
                'organization_id' => $organization_id
            ]);

            return $ajax->success()
                ->message('Organization users list updated successfully')
                ->appendParam('organization_name',$organization->organization_name)
                ->jscallback('ajax_org_access')
                ->response();

        }else{
            return $ajax->fail()
                ->message('Access denied !')
                ->jscallback()
                ->response();
        }
    }
}
