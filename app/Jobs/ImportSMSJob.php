<?php

namespace App\Jobs;

use App\Customer;
use App\SystemConfig;
use App\CitizenImport;
use App\ImportLog;
use App\RejectLog;

use DB;
use Exception;
use Auth;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class ImportSMSJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

	protected $userID;
	protected $importpath;
	protected $filename;
	protected $filelocation;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userID, $importpath, $filename, $filelocation)
    {
        $this->userID = $userID;
		$this->importpath = $importpath;
		$this->filename = $filename;
		$this->filelocation = $filelocation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		set_time_limit(0);
		ini_set('memory_limit', '1024M');
		$filetxt = file($this->filelocation);
		$readcount = 0;
		$insertcount = 0;
		$rejectcount = 0;
		$filename = iconv('UTF-8','windows-874',$this->filename);
		$log = new ImportLog;
		$log->contact_type = "Import SMS";
		$log->file_name = $this->filename;
		$log->file_instance = $this->filename;
		$log->start_date_time = date('Ymd H:i:s');
		$log->save();
		
		// $filename = iconv('UTF-8','windows-874',$this->filename);
		// $f->move($importpath, $filename);				

		$linecount = 1;

		foreach($filetxt as $line) {
			if ($linecount == 1) {
			} else {
				$readcount += 1;
				$item = explode('|',$line);
				
				if (!empty($item)) {
					try {
						$acn = trim($item[0]);
						$xname = trim($item[1]);
						$aph = trim($item[2]);
						Customer::where("aph",$aph)->where("acn",$acn)->update(["aph_use_flag" => 0]);
						$insertcount += 1;
					} catch (Exception $e) {
						$reject = new RejectLog;
						$reject->file_id = 888;
						$reject->file_instance = $this->filename;
						$reject->reject_date = date('Ymd H:i:s');
						//$reject->cif_no = $item[0];
						//$reject->citizen_id = trim(substr($l,11,13));
						//$reject->birth_date = $cz->dob;
						$reject->reject_desc = substr($item[0],0,100) . "|" . substr($e,0,150);
						$reject->save();
						$rejectcount += 1;
					}
				}
			}
			$linecount += 1;
		}				
		rename($this->importpath.$this->filename, $this->importpath."archive\\".$filename);	
		ImportLog::where("file_name",$this->filename)->update(['end_date_time' => date('Ymd H:i:s'), 'total_record_read_file' => $readcount, 'total_record_insert_table' =>  $insertcount, 'total_record_rejected' => $rejectcount]);		

    }
}
