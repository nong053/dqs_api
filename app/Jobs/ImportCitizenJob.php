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
		$filetxt = file($this->filelocation);
		$readcount = 0;
		$insertcount = 0;
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

				Customer::where("acn",trim(substr($l,0,11)))->update(['citizen_import_flag' => 1, 'citizen_import_date' => date('Ymd H:i:s')]);
				$insertcount += 1;
			} catch (QueryException $e) {
				$reject = new RejectLog;
				$reject->file_id = 999;
				$reject->file_instance = $this->filename;
				$reject->reject_date = date('Ymd H:i:s');
				$reject->cif_no = (int)trim(substr($l,0,11));
				$reject->citizen_id = trim(substr($l,11,13));
				//$reject->birth_date = $cz->dob;
				$reject->reject_desc = substr($e,0,254);;
				$reject->save();
			} catch (Exception $e) {
				$reject = new RejectLog;
				$reject->file_id = 999;
				$reject->file_instance = $this->filename;
				$reject->reject_date = date('Ymd H:i:s');
				$reject->cif_no = (int)trim(substr($l,0,11));
				$reject->citizen_id = trim(substr($l,11,13));
				//$reject->birth_date = $cz->dob;
				$reject->reject_desc = substr($e,0,254);;
				$reject->save();			
			}

		}	

		rename($this->importpath.$this->filename, $this->importpath."archive\\".$this->filename);

		$log = new ImportLog;
		$log->contact_type = "Import Citizen";
		$log->file_name = $this->filename;
		$log->file_instance = $this->filename;
		$log->start_date_time = $this->start_at;
		$log->end_date_time = date('Ymd H:i:s');
		$log->total_record_read_file = $readcount;
		$log->total_record_insert_table = $insertcount;
		$log->save();		
    }
}
