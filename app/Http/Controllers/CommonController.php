<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseConnection;
use App\Helpers\Helper;
use App\Library\Ajax;
use App\Model\Extensionorganizationmapping;
use App\Model\Organization;
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


class CommonController extends Controller
{
    /**
     * @param Request $request
     * @param Ajax $ajax
     * @return mixed
     */
    public function getExtensionsList_v1(Request $request,Ajax $ajax){
        $lists = ($request->input('list') && !empty($request->input('list'))) ?
                    explode('-',$request->input('list')) :
                    [];

        $eExtensions = Extensionorganizationmapping::where('organization_id',Auth::user()->organization->id)
            ->where('server_id',Auth::user()->organization->server_ID)
            ->where('is_active',1)
            ->pluck('extension')
            ->toArray();

        $connection = DatabaseConnection::setConnection(Auth::user()->organization->server);
        $ids_ordered = implode(',', $lists);
        $eExtensionsData = $connection->table('users')
            ->whereIn('extension',$eExtensions)
            ->orderByRaw(DB::raw("FIELD(extension, $ids_ordered)"))
            ->get(['extension','name']);

        $title = 'Choose Extensions';
        $content = View::make('common.choose',[
            'eExtensions' => $eExtensionsData,
            'lists' => $lists
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

    /**
     * @param Request $request
     * @param Ajax $ajax
     * @return mixed
     */
    public function updateExtensionsList_v1(Request $request,Ajax $ajax){
        $grplist = $request->input('grplist',[]);
        return $ajax->success()
            ->appendParam('grplist', $grplist)
            ->jscallback('ajax_extensions_list')
            ->response();
    }

    /**
     * @param Request $request
     * @param Ajax $ajax
     * @return mixed
     */
    public function getExtensionsList(Request $request,Ajax $ajax){
        $eExtensions = [];
        $eExtensionRange = Extensionorganizationmapping::where('organization_id',Auth::user()->organization->id)
            ->where('server_id',Auth::user()->organization->server_ID)
            ->pluck('extension')
            ->toArray();

        $cConnection = DatabaseConnection::setConnection(Auth::user()->organization->server);
        $eExtensions = $cConnection->table('users')
            ->whereIn('extension',$eExtensionRange)
            ->orderBy('extension')
            ->get(['extension','name']);

        $title = 'Choose Extensions';
        $content = View::make('common.choose',[
            'eExtensions' => $eExtensions,
            'lists' => []
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

    /**
     * @param Request $request
     * @param Ajax $ajax
     * @return mixed
     */
    public function updateExtensionsList(Request $request,Ajax $ajax){
        $type = $request->input('type','');
        $grplist = $request->input('grplist','');

        return $ajax->success()
            ->appendParam('grplist', $type == 'internal' ?
                $grplist :
                preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '$1$2$3', $grplist). "\n".'::'.$grplist)
            ->jscallback($type == 'internal' ? 'ajax_extensions_list' : 'ajax_external_extensions_list')
            ->response();
    }

    public function addExternalExtension(Ajax $ajax){

        $title = 'External Extension';
        $content = View::make('common.external-extension')->render();
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
}
