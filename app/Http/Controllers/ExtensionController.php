<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseConnection;
use App\Helpers\Helper;
use App\Library\Ajax;
use App\Model\Extensionorganizationmapping;
use App\Model\Phoneorganizationmapping;
use http\Client\Curl\User;
use Illuminate\Http\Request;
use DB;
use Config;
use Auth;
use Crypt;
use Validator;
use Illuminate\Support\Facades\Artisan;
use \Illuminate\Support\Facades\View as View;


class ExtensionController extends Controller
{
    public function index(){
        return view(Helper::OrgNotFound(Auth::user()->IsOrganizationAssigned,'extensions.index'),[
            'organization_name' => Auth::user()->IsOrganizationAssigned ? Auth::user()->organization->organization_name : '',
            'redirectTo' => Route('organization.index')
        ]);
    }

    public function getExtensions(Request $request,Ajax $ajax){
        $tabid = $request->input('tabid');
        $sort = $request->input('sort') ? $request->input('sort') : '';
        $dir = $request->input('dir') ? $request->input('dir') : '';
        $page = $request->input('page',1);
        $records_per_page = 5; //config('constant.record_per_page');
        $sort = ($sort == "") ? "Order By CampaignId DESC" : $sort == 'CampaignId' ? "Order By CampaignId DESC" : "Order By $sort $dir";
        $rType = $request->input('rtype','');
        $filters = $request->input('filters',[]);
        $position = ($page-1) * $records_per_page;

        //$user = \App\User::find(Auth::id());
        try {
            //$eExtensionArr = explode('-',Auth::user()->organization->extension_range);
            //$eExtensionRange = range($eExtensionArr[0],$eExtensionArr[1]);
            $cConnection = DatabaseConnection::setConnection(Auth::user()->organization->server);
            //$users = $connection->select(DB::raw("SELECT extension,name FROM users WHERE extension BETWEEN ".$extensionrange[0]." AND ".$extensionrange[1]));

            $eExtenstionsLists = Extensionorganizationmapping::where('organization_id', Auth::user()->organization->id)
                ->where('server_id', Auth::user()->organization->server_ID)
                ->pluck('extension')
                ->toArray();

            $eExtensions = $cConnection->table('users')
                ->whereIn('extension',$eExtenstionsLists)
                ->skip($position)
                ->take($records_per_page)
                ->orderBy('extension')
                ->get();

            $eExtensions = collect($eExtensions)->map(function($x){ return (array) $x; })->toArray();
            $tabName = 'Extensions';
            if($rType == 'pagination'){
                $html = View::make('extensions.tabs.list.table',['eExtensions' => $eExtensions,'tab' => $tabName])->render();
            }else{
                $html = View::make('extensions.tabs.list.index',['eExtensions' => $eExtensions,'tab' => $tabName])->render();
            }

            $tTotalExtensions = $cConnection->table('users')->whereIn('extension',$eExtenstionsLists)->count();

            $paginationhtml = View::make('extensions.tabs.list.pagination-html',[
                'total_records' => $tTotalExtensions,
                'records' => $eExtensions,
                'position' => $position,
                'records_per_page' => $records_per_page,
                'page' => $page,
                'tab' => $tabName
            ])->render();

            return $ajax->success()
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

    public function editExtension($enc_extension_id){
        $connection = DatabaseConnection::setConnection(Auth::user()->organization->server);
        $pPhoneNumbers = Phoneorganizationmapping::where('organization_id',Auth::user()->organization->id)
            ->where('server_id',Auth::user()->organization->server_ID)
            ->get(['phone_number']);

        if($enc_extension_id != '0'){
            $extension_id = Crypt::decrypt($enc_extension_id);
            $extension = $connection->table('users')->where('extension',$extension_id)->first(['extension','name','outboundcid']);
            $smartfollow = $connection->table('findmefollow')->where('grpnum',$extension_id)->first();

            $title = 'Edit Extension';
            /*if(!$extension){
                return $ajax->fail()
                    ->message('Extension not found')
                    ->jscallback()
                    ->response();
            }*/

            return view(Helper::OrgNotFound(Auth::user()->IsOrganizationAssigned,'extensions.form.add'),[
                'organization_name' => Auth::user()->IsOrganizationAssigned ? Auth::user()->organization->organization_name : '',
                'redirectTo' => Route('dashboard.index'),
                'extension' => $extension,
                'pPhoneNumbers' => $pPhoneNumbers,
                'sStrategies' => Controller::Strategies,
                'smartfollow' => $smartfollow
            ]);
        }else{
            return view(Helper::OrgNotFound(Auth::user()->IsOrganizationAssigned,'extensions.form.add'),[
                'organization_name' => Auth::user()->IsOrganizationAssigned ? Auth::user()->organization->organization_name : '',
                'redirectTo' => Route('dashboard.index'),
                'pPhoneNumbers' => $pPhoneNumbers,'sStrategies' => Controller::Strategies
            ]);
        }
    }

    public function updateExtension(Request $request,Ajax $ajax){
        $rules = [
            'name' => 'required',
            'outboundname' => 'required',
            'outboundnumber' => 'required',
        ];

        $messages = [
            'name.required' => 'Extension name is required',
            'outboundname.required' => 'Outbound name is required',
            'outboundnumber.required' => 'Outbound number is required'

        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $ajax->fail()
                ->form_errors($validator->errors())
                ->jscallback()
                ->response();
        }

        $connection = DatabaseConnection::setConnection(Auth::user()->organization->server);
        $extensionExist = $connection->table('users')->where('extension',$request->input('extension'))->count();

        if($extensionExist > 0){
            $outboundname = $request->input('outboundname');
            $outboundnumber = $request->input('outboundnumber');
            $outboundcid = '"'.$outboundname.'" <'.$outboundnumber.'>';

            $connection->table('users')
                ->where('extension',$request->input('extension'))
                ->update([
                    'name' => trim($request->input('name')),
                    'outboundcid' => trim($outboundcid),
                ]);
        }
        return $ajax->success()
            ->jscallback()
            ->reload_page(true)
            ->message('Extension updated successfully')
            ->response();

    }

    public function updateSmartFollow(Request $request,Ajax $ajax){
        $rules = [
            'pre_ring' => 'required',
            'grptime' => 'required',
            'strategy' => 'required',
            //'grplist' => 'required',
            //'grppre' => 'required',
        ];

        $messages = [
            'pre_ring.required' => 'Ring main extension name is required',
            'grptime.required' => 'Ring other extensions is required',
            'strategy.required' => 'Ring type is required',
            //'grplist.required' => 'Extensions to ring is required',
            //'grppre.required' => 'CID Prefix is required'

        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $ajax->fail()
                ->form_errors($validator->errors())
                ->jscallback()
                ->response();
        }

        $connection = DatabaseConnection::setConnection(Auth::user()->organization->server);
        $smartfollowExist = $connection->table('findmefollow')->where('grpnum',$request->input('grpnum'))->count();
        $newgrplist = [];

        $grplists = !empty($request->input('grplist')) ? json_decode($request->input('grplist'),true) : [];
        foreach ($grplists as $grplist){
            $symbol = '';
            if($grplist['type'] == 'external') $symbol = '#';
            array_push($newgrplist,$grplist['id'].$symbol);
        }

        if($smartfollowExist > 0){
            $connection->table('findmefollow')
                ->where('grpnum',$request->input('grpnum'))
                ->update([
                    'pre_ring' => trim($request->input('pre_ring')),
                    'grptime' => trim($request->input('grptime')),
                    'strategy' => trim($request->input('strategy')),
                    'grplist' =>  count($newgrplist) > 0 ? implode('-',$newgrplist) : '',
                    'grppre' => trim($request->input('grppre'))
                ]);
        }
        return $ajax->success()
            ->jscallback()
            ->reload_page(true)
            ->message('Smart follow updated successfully')
            ->response();
    }
}
