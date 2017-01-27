<?php

namespace App\Jobs;

use App\Customer;
use App\SystemConfig;
use App\CitizenImport;
use App\ImportLog;
use App\RejectLog;
use App\File;

use DB;
use Exception;
use Auth;
use DateTime;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class ImportCitizenJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

	protected $userID;
	protected $importpath;
	protected $filename;
	protected $filelocation;
	protected $start_at;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userID, $importpath, $filename, $filelocation, $start_at)
    {
        $this->userID = $userID;
		$this->importpath = $importpath;
		$this->filename = $filename;
		$this->filelocation = $filelocation;
		$this->start_at = $start_at;
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
		$start_date = date('Ymd H:i:s');
		$filename = iconv('UTF-8','windows-874',$this->filename);
		$contact_type = File::find(24);
		if (empty($contact_type)) {
			$contact_type = '';
		} else {
			$contact_type = $contact_type->contact_type;
		}
		
		$log = ImportLog::where("file_name",$this->filename)->where("contact_type",$contact_type);
		
		if ($log->count() == 0) {
			$log = new ImportLog;
			$log->contact_type = $contact_type;
			$log->file_name = $this->filename;
			$log->file_instance = $this->filename;
			$log->start_date_time = $start_date;
			$log->save();

		} else {
			ImportLog::where("file_name",$this->filename)->where("contact_type",$contact_type)->update([
				'start_date_time' => $start_date,
				'total_record_read_file' => 0,
				'total_record_insert_table' => 0,
				'total_record_rejected' => 0,
				'end_date_time' => null,
				'proessing_time' => null
			]);

		}
		foreach($filetxt as $l) {
			//$item = explode('|',$line);

			$readcount += 1;

			//$line = iconv("tis-620", "utf-8", $l);
			try {			
			
				$cz = CitizenImport::find((int)trim(substr($l,0,11)));
				if (empty($cz)) {
					$cz = new CitizenImport;
					$cz->ref_no = trim(substr($l,0,11));
					$cz->pid = trim(substr($l,11,13));
					$cz->fname = iconv("tis-620", "utf-8", trim(substr($l,24,30)));
					$cz->lname = iconv("tis-620", "utf-8", trim(substr($l,54,30)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,84,8)))) ? $cz->dob = null : $cz->dob = iconv("tis-620", "utf-8", trim(substr($l,84,8)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,92,1)))) ? $cz->sex = null : $cz->sex = iconv("tis-620", "utf-8", trim(substr($l,92,1)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,93,13)))) ? $cz->npid = null : $cz->npid = iconv("tis-620", "utf-8", trim(substr($l,93,13)));
					$cz->ntitle = iconv("tis-620", "utf-8", trim(substr($l,106,30)));
					$cz->nfname = iconv("tis-620", "utf-8", trim(substr($l,136,30)));
					$cz->nlname = iconv("tis-620", "utf-8", trim(substr($l,166,30)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,196,8)))) ? $cz->ndob = null : $cz->ndob = iconv("tis-620", "utf-8", trim(substr($l,196,8)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,204,1)))) ? $cz->nsex = null : $cz->nsex = iconv("tis-620", "utf-8", trim(substr($l,204,1)));
					$cz->hno = iconv("tis-620", "utf-8", trim(substr($l,205,16)));
					$cz->moo = iconv("tis-620", "utf-8", trim(substr($l,221,6)));
					$cz->trok = iconv("tis-620", "utf-8", trim(substr($l,227,40)));
					$cz->soi = iconv("tis-620", "utf-8", trim(substr($l,267,40)));
					$cz->thanon = iconv("tis-620", "utf-8", trim(substr($l,307,40)));
					$cz->thumbol = iconv("tis-620", "utf-8", trim(substr($l,347,40)));
					$cz->amphur = iconv("tis-620", "utf-8", trim(substr($l,387,40)));
					$cz->province = iconv("tis-620", "utf-8", trim(substr($l,427,40)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,467,2)))) ? $cz->flag = null : $cz->flag = iconv("tis-620", "utf-8", trim(substr($l,467,2)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,469,2)))) ? $cz->flag_1 = null : $cz->flag_1 = iconv("tis-620", "utf-8", trim(substr($l,469,2)));
					$cz->thai_flag = 1;
					$cz->manual_add_flag = 0;
					$cz->created_by = $this->userID;
					$cz->updated_by = $this->userID;

					$cz->save();				
				} else {
					$cz->ref_no = trim(substr($l,0,11));
					$cz->pid = trim(substr($l,11,13));
					$cz->fname = iconv("tis-620", "utf-8", trim(substr($l,24,30)));
					$cz->lname = iconv("tis-620", "utf-8", trim(substr($l,54,30)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,84,8)))) ? $cz->dob = null : $cz->dob = iconv("tis-620", "utf-8", trim(substr($l,84,8)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,92,1)))) ? $cz->sex = null : $cz->sex = iconv("tis-620", "utf-8", trim(substr($l,92,1)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,93,13)))) ? $cz->npid = null : $cz->npid = iconv("tis-620", "utf-8", trim(substr($l,93,13)));
					$cz->ntitle = iconv("tis-620", "utf-8", trim(substr($l,106,30)));
					$cz->nfname = iconv("tis-620", "utf-8", trim(substr($l,136,30)));
					$cz->nlname = iconv("tis-620", "utf-8", trim(substr($l,166,30)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,196,8)))) ? $cz->ndob = null : $cz->ndob = iconv("tis-620", "utf-8", trim(substr($l,196,8)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,204,1)))) ? $cz->nsex = null : $cz->nsex = iconv("tis-620", "utf-8", trim(substr($l,204,1)));
					$cz->hno = iconv("tis-620", "utf-8", trim(substr($l,205,16)));
					$cz->moo = iconv("tis-620", "utf-8", trim(substr($l,221,6)));
					$cz->trok = iconv("tis-620", "utf-8", trim(substr($l,227,40)));
					$cz->soi = iconv("tis-620", "utf-8", trim(substr($l,267,40)));
					$cz->thanon = iconv("tis-620", "utf-8", trim(substr($l,307,40)));
					$cz->thumbol = iconv("tis-620", "utf-8", trim(substr($l,347,40)));
					$cz->amphur = iconv("tis-620", "utf-8", trim(substr($l,387,40)));
					$cz->province = iconv("tis-620", "utf-8", trim(substr($l,427,40)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,467,2)))) ? $cz->flag = null : $cz->flag = iconv("tis-620", "utf-8", trim(substr($l,467,2)));
					empty(iconv("tis-620", "utf-8", trim(substr($l,469,2)))) ? $cz->flag_1 = null : $cz->flag_1 = iconv("tis-620", "utf-8", trim(substr($l,469,2)));
					$cz->thai_flag = 1;
					$cz->manual_add_flag = 0;
					$cz->updated_by = $this->userID;

					$cz->save();								
				}
				try	{
					Customer::where("acn",trim(substr($l,0,11)))->update(['citizen_import_flag' => 1, 'citizen_import_date' => date('Ymd H:i:s')]);
					$insertcount += 1;
				} catch (Exception $e) {
					$rejectrec = CitizenImport::find(trim(substr($l,0,11)));
					if (!empty($rejectrec)) {
						$rejectrec->delete();
					}
					$reject = new RejectLog;
					$reject->file_id = 24;
					$reject->file_instance = $this->filename;
					$reject->reject_date = date('Ymd H:i:s');
					$reject->cif_no = (int)trim(substr($l,0,11));
					//$reject->citizen_id = trim(substr($l,11,13));
					//$reject->birth_date = $cz->dob;
					$reject->reject_desc = substr($e,0,254);;
					$reject->save();
					$rejectcount += 1;				
				}
			} catch (Exception $e) {
				// $rejectrec = CitizenImport::find(trim(substr($l,0,11)));
				// if (!empty($rejectrec)) {
					// $rejectrec->delete();
				// }			
				$reject = new RejectLog;
				$reject->file_id = 24;
				$reject->file_instance = $this->filename;
				$reject->reject_date = date('Ymd H:i:s');
				$reject->cif_no = (int)trim(substr($l,0,11));
				//$reject->citizen_id = trim(substr($l,11,13));
				//$reject->birth_date = $cz->dob;
				$reject->reject_desc = substr($e,0,254);;
				$reject->save();	
				$rejectcount += 1;
			}

		}	

		rename($this->importpath.$this->filename, $this->importpath."archive\\".$filename);
		
		$end_date = date('Ymd H:i:s');
		$end_date = new DateTime($end_date);
		$start_date = new DateTime($start_date);
		$interval = $start_date->diff($end_date);

		$minutes = $interval->days * 24 * 60;
		$minutes += $interval->h * 60;
		$minutes += $interval->i;

	
		ImportLog::where("file_name",$this->filename)->where("contact_type",$contact_type)->update(['end_date_time' => $end_date, 'total_record_read_file' => $readcount, 'total_record_insert_table' =>  $insertcount, 'total_record_rejected' => $rejectcount, 'proessing_time' => floor($minutes / 60) . 'h ' . $minutes % 60 . 'm']);

    }
}
