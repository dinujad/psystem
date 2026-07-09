<?php

namespace Modules\Accounting\Http\Controllers;

use App\System;
use Composer\Semver\Comparator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'accounting';
        $this->appVersion = config('accounting.module_version');
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();

        //Check if installed or not.
        $is_installed = System::getProperty($this->module_name . '_version');
        if (!empty($is_installed)) {
            abort(404);
        }

        $action_url = action('\Modules\Accounting\Http\Controllers\InstallController@install');
        $intruction_type = 'uf';
        $module_display_name = 'Accounting';

        return view('install.install-module')
            ->with(compact('action_url', 'intruction_type', 'module_display_name'));
    }

    /**
     * Initialize all install functions
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Installing Accounting Module
     */
    public function install()
    {
        try {
            $is_installed = System::getProperty($this->module_name . '_version');
            if (!empty($is_installed)) {
                abort(404);
            }

            DB::transaction(function () {
                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => 'Accounting']);
                Artisan::call('module:publish', ['module' => 'Accounting']);
                System::addProperty($this->module_name . '_version', $this->appVersion);
            });
            
            $output = ['success' => 1,
                    'msg' => 'Accounting module installed succesfully'
                ];
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }

        return redirect()
                ->action('\App\Http\Controllers\Install\ModulesController@index')
                ->with('status', $output);
    }

    /**
     * Uninstall
     * @return Response
     */
    public function uninstall()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name . '_version');

            $output = ['success' => true,
                            'msg' => __("lang_v1.success")
                        ];
        } catch (\Exception $e) {
            $output = ['success' => false,
                        'msg' => $e->getMessage()
                    ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * update module
     * @return Response
     */
    public function update()
    {
        //Check if accounting_version is same as appVersion then 404
        //If appVersion > accounting_version - run update script.
        //Else there is some problem.
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            $accounting_version = System::getProperty($this->module_name . '_version');

            if (empty($accounting_version)) {
                $output = [
                    'success' => false,
                    'msg' => 'Accounting module is not installed yet. Please install it first.',
                ];

                return redirect()
                    ->action('\App\Http\Controllers\Install\ModulesController@index')
                    ->with('status', $output);
            }

            if (Comparator::greaterThan($this->appVersion, $accounting_version)) {
                ini_set('max_execution_time', 0);
                ini_set('memory_limit', '512M');
                $this->installSettings();

                DB::transaction(function () {
                    DB::statement('SET default_storage_engine=INNODB;');
                    Artisan::call('module:migrate', ['module' => 'Accounting']);
                    Artisan::call('module:publish', ['module' => 'Accounting']);
                    System::setProperty($this->module_name . '_version', $this->appVersion);
                });
            } else {
                $output = [
                    'success' => true,
                    'msg' => 'Accounting module is already up to date (version '.$accounting_version.').',
                ];

                return redirect()
                    ->action('\App\Http\Controllers\Install\ModulesController@index')
                    ->with('status', $output);
            }
            
            $output = ['success' => 1,
                        'msg' => 'Accounting module updated Succesfully to version ' . $this->appVersion . ' !!'
                    ];

            return redirect()->back()->with(['status' => $output]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];

            return redirect()
                ->action('\App\Http\Controllers\Install\ModulesController@index')
                ->with('status', $output);
        }
    }
}
