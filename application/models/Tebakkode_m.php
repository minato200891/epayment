<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Tebakkode_m extends CI_Model {
    function __construct(){
        parent::__construct();
        $this->load->database();
    }
    function log_events($signature, $body)
    {
        $this->db->set('signature', $signature)
        ->set('events', $body)
        ->insert('eventlog');
        return $this->db->insert_id();
    }
	function getUser($userId)
	{
		$data = $this->db->where('user_id', $userId)   ->get('users')->row_array();
		if(count($data) > 0)    return $data;
		return false;
	}
	function getDiskon($kddiskon)
	{
		$data = $this->db->where('Kd_Diskon', $userId)   ->get('t_diskon')->row_array();
		if(count($data) > 0)    return $data;
		return false;
	}
	function saveUser($profile)
	{
		$this->db->set('user_id', $profile['userId'])
		->set('display_name', $profile['displayName'])
		->insert('users');
		return $this->db->insert_id();
	}
	function cekAccsend($acc)
	{
		 $aa = $this->db->query("SELECT * FROM t_payer WHERE Acc_Number='$acc'");
		 return $aa->num_rows();

	}
	function cekKodevoucher($kdvoc)
	{
		 $qq = $this->db->query("SELECT * FROM t_voucher WHERE kdvoucher='$kdvoc' and Status<>'1'");
		 return $qq->num_rows();
	}
	function cekToko($kdtoko)
	{
		 $tk = $this->db->query("SELECT * FROM t_accountmct WHERE Account_Code='$kdtoko'");
		 return $tk->num_rows();
	}
	function cekDiskon($kddiskon)
	{
		 $dk = $this->db->query("SELECT * FROM t_diskon WHERE Kd_Diskon='$kddiskon'");
		 return $dk->num_rows();
	}
	function cekAccdest($accdest)
	{
		 $bb = $this->db->query("SELECT * FROM t_payer WHERE Acc_Number='$accdest'");
		 return $bb->num_rows();
	}
	function saveTemp($accsend,$accdest,$jumlah,$jenis)
	{
		 $this->db->set('Account_Number_re', $accsend);
		 $this->db->set('Account_Number_de', $accdest);
		 $this->db->set('jumlah', $jumlah);
		 $this->db->set('jenis', $jenis);
		 $this->db->insert('t_temporder');
		 return $this->db->insert_id();
	}
	function cekProductid($prodid)
	{
		 $cc = $this->db->query("SELECT * FROM t_product WHERE Product_Id='$prodid'");
		 return $cc->num_rows();
	}
	function saveBuyTemp($accsend,$prodid,$jumlah,$jenis)
	{
		 $this->db->set('Account_Number_re', $accsend);
		 $this->db->set('Product_id', $prodid);
		 $this->db->set('jumlah', $jumlah);
		 $this->db->set('jenis', $jenis);
		 $this->db->insert('t_temporder');
		 return $this->db->insert_id();
	}
	function cekPin($pinid,$payid)
	{
		 $dd = $this->db->query("SELECT * FROM t_signature WHERE Pin_number='$pinid'");
		 return $dd->num_rows();
	}
	function cekTransnum($trnum)
	{
		 $ee = $this->db->query("SELECT * FROM t_transaction WHERE transaction_num='$trnum'");
		 return $ee->num_rows();
	}
	function updateTrans($trnum)
	{
		 $this->db->set('Status', 'PAID')->where('transaction_num',$trnum);
		 $this->db->update('t_transaction');
		 return $this->db->insert_id();
	}
	function cekSaldo($anum)
	{
		   $query = $this->db->query("SELECT Saldo FROM t_payer where Acc_Number='$anum'");
		   return $query;
	}
	function getTransaksi($trid)
	{
		   $query = $this->db->query("SELECT * FROM t_temporder where Id='$trid'");
		   return $query;
	}
	function getVoc($kdvoc)
	{
		   $query = $this->db->query("SELECT * FROM t_voucher WHERE kdvoucher='$kdvoc'");
		   return $query;
	}
	function getPrice($pdid)
	{
		   $query = $this->db->query("SELECT * FROM t_product where Product_Id='$pdid'");
		   return $query;
	}
	function getPayer($acnum)
	{
		   $query = $this->db->query("SELECT * FROM t_payer where Acc_Number='$acnum'");
		   return $query;
	}
	function getTransTotal($trnum)
	{
		   $query = $this->db->query("SELECT * FROM t_transaction where transaction_num='$trnum'");
		   return $query;
	}
	function updatePayer($anum,$saldo)
	{
		 $this->db->set('Saldo',$saldo )->where('Acc_Number',$anum);
		 $this->db->update('t_payer');
		 return $this->db->insert_id();
	}
	function updateVoucher($kdvoc)
	{
		 $this->db->set('Status','1' )->where('kdvoucher',$kdvoc);
		 $this->db->update('t_voucher');
		 return $this->db->insert_id();
	}
	function cekTransid($trid)
	{
		 $ff = $this->db->query("SELECT * FROM t_temporder WHERE Id='$trid'");
		 return $ff->num_rows();
	}
	function saveTransTrf($trnum,$anum,$payid,$jml,$jenis,$date,$status)
	{
		 $this->db->set('Transaction_Num', $trnum);
		 $this->db->set('Account_Num', $anum);
		 $this->db->set('Payer_Id', $payid);
		 $this->db->set('Transaction_Total', $jml);
		 $this->db->set('Transaction_Category', $jenis);
		 $this->db->set('Transaction_Date', $date);
		 $this->db->set('Status', $status);
		 $this->db->insert('t_transaction');
		 return $this->db->insert_id();
	}
	function saveTransBuy($trnum,$anum,$payid,$jml,$jenis,$date,$status,$kdtoko)
	{
		 $this->db->set('Transaction_Num', $trnum);
		 $this->db->set('Account_Num', $anum);
		 $this->db->set('Payer_Id', $payid);
		 $this->db->set('Transaction_Total', $jml);
		 $this->db->set('Transaction_Category', $jenis);
		 $this->db->set('Transaction_Date', $date);
		 $this->db->set('Status', $status);
		 $this->db->set('Account_code', $kdtoko);
		 $this->db->insert('t_transaction');
		 return $this->db->insert_id();
	}
	function saveTransDetail($trnum,$prodid,$jml,$total)
	{
		 $this->db->set('Transaction_Number', $trnum);
		 $this->db->set('Product_Id', $prodid);
		 $this->db->set('Amount', $jml);
		 $this->db->set('Total_Price', $total);
		 $this->db->insert('t_transdetail');
		 return $this->db->insert_id();
	}
}
