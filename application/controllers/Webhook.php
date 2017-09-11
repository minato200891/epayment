<?php defined('BASEPATH') OR exit('No direct script access allowed');

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;

use \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

class Webhook extends CI_Controller {
    private $events;
    private $signature;
    private $bot;
    private $user;
    function __construct()
    {
        parent::__construct();
        $this->load->model('tebakkode_m');
		$httpClient = new CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
		$this->bot  = new LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
    }
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "Hello Coders!";
            header('HTTP/1.1 400 Only POST method allowed');
            exit;
        }
        // get request
        $body = file_get_contents('php://input');
        $this->signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE'])
                ? $_SERVER['HTTP_X_LINE_SIGNATURE']
                : "-";
        $this->events = json_decode($body, true);
        $this->tebakkode_m->log_events($this->signature, $body);
		foreach ($this->events['events'] as $event)
		   {
		   // skip group and room event
		   if(! isset($event['source']['userId'])) continue;
		   // get user data from database
		   $this->user = $this->tebakkode_m     ->getUser($event['source']['userId']);
		   // respond event
		   if($event['type'] == 'message'){
			   if(method_exists($this, $event['message']['type'].'Message')){
			   $this->{$event['message']['type'].'Message'}($event);
			   }
		   }
		   else {
			   if(method_exists($this, $event['type'].'Callback')){
			   $this->{$event['type'].'Callback'}($event);
			   }
		   }
		}
    }
	
	private function followCallback($event)
	{
			$res = $this->bot->getProfile($event['source']['userId']);
			if ($res->isSucceeded())
			{
				$profile = $res->getJSONDecodedBody();
				// save user data
				$this->tebakkode_m->saveUser($profile);
				// send welcome message
				$message = "Salam kenal, " . $profile['displayName'] . "!\n";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
			}
		}
	private function textMessage($event)
	{
			$userMessage = $event['message']['text'];
			$msg = strtoupper($userMessage);
			$pecah = explode(" ", $msg);
			if($pecah[0]=="TRF")
			{
				$accountsend=$pecah[1];
				$accountdest=$pecah[2];
				$jumlahtrans=$pecah[3];
				$accsendex=$this->tebakkode_m->cekAccsend($accountsend);
				$accdestex=$this->tebakkode_m->cekAccdest($accountdest);
				if($accsendex==0){
				$message="Nomor Akun Pengirim Anda Tidak Diketahui";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);	
				}
				else if($accdestex==0){
				$message="Nomor Akun Penerima Anda Tidak Diketahui";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
				else {
					$idtrans=$this->tebakkode_m->saveTemp($accountsend,$accountdest,$jumlahtrans,"TRF");
					$message="Proses Anda Sedang Di Proses Dengan Id ".$idtrans;
					$textMessageBuilder = new TextMessageBuilder($message);
					$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
			} 
			else if($pecah[0]=="BUY") {
				$accountbuy=$pecah[1];
				$productid=$pecah[2];
				$jumlahbuy=$pecah[3];
				$accbuy=$this->tebakkode_m->cekAccsend($accountbuy);
				$prdid=$this->tebakkode_m->cekProductid($productid);
				if($accbuy==0){
				$message="Nomor Akun Pembeli Anda Tidak Diketahui";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);	
				}
				else if($prdid==0){
				$message="Kode Produk Yang Anda Masukkan Tidak Di Ketahui";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
				else {
					$date=date("Y-m-d");
					$buyid=$this->tebakkode_m->saveBuyTemp($accountbuy,$productid,$jumlahbuy,"BUY");
					$message="Proses Anda Sedang Di Proses Dengan Id ".$buyid;
					$textMessageBuilder = new TextMessageBuilder($message);
					$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
			}
			else if($pecah[0]=="PAY") {
				$acnum=$pecah[1];
				$pins=$pecah[2];
				$transnum=$pecah[3];
				$pinex=$this->tebakkode_m->cekPin($pins,$acnum);
				$trnum=$this->tebakkode_m->cekTransnum($transnum);
				if($transnum==0){
				$message="Transaksi Anda Tidak Diketahui";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);	
				}
				else if($pinex==0){
				$message="PIN Anda Salah";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
				else {
					$qrypy=$this->tebakkode_m->getPayer($acnum);
					foreach ($qrypy->result() as $rowpy)
					{
							$saldopy=$rowpy->Saldo;
					}
					$qrytr=$this->tebakkode_m->getTransTotal($transnum);
					foreach ($qrytr->result() as $rowtr)
					{
							$jumlahtr=$rowtr->Transaction_Total;
					}
					$date=date("Y-m-d");
					$saldoakhir=$saldopy - $jumlahtr;
					$this->tebakkode_m->updatePayer($acnum,$saldoakhir);
					$this->tebakkode_m->updateTrans($transnum);
					$message="Proses Anda Telah DiProses, Terima Kasih";
					$textMessageBuilder = new TextMessageBuilder($message);
					$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
			}
			else if($pecah[0]=="TOPUP") {
				$pinid=$pecah[1];
				$anum=$pecah[2];
				$kdvoc=$pecah[3];
				$kdvocex=$this->tebakkode_m->cekKodevoucher($kdvoc);
				$pinidex=$this->tebakkode_m->cekPin($pinid,$anum);
				if($kdvocex==0){
				$message="Kode Voucher Anda Tidak Diketahui";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);	
				}
				else if($pinidex==0){
				$message="Pin Anda Salah";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
				else {
					$date=date("Y-m-d");
					$qry=$this->tebakkode_m->cekSaldo($anum);
					foreach ($qry->result() as $row)
					{
							$saldo=$row->Saldo;	
					}
					$qryvoc=$this->tebakkode_m->getVoc($kdvoc);
					foreach ($qryvoc->result() as $rowvoc)
					{
							$saldovoc=$rowvoc->nilai_voucher;	
					}
					$saldotop=$saldo + $saldovoc;
					$this->tebakkode_m->updatePayer($anum,$saldotop);
					$this->tebakkode_m->updateVoucher($kdvoc);
					$message="Transaksi Anda Telah Di Proses";
					$textMessageBuilder = new TextMessageBuilder($message);
					$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
			}
			else if($pecah[0]=="CEKSALDO") {
				$pinid=$pecah[1];
				$accnum=$pecah[2];
				$pinidex=$this->tebakkode_m->cekPin($pinid,$accnum);
				if($pinidex==0){
				$message="Pin Anda Salah";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
				else {
					$date=date("Y-m-d");
					$qry=$this->tebakkode_m->cekSaldo($accnum);
					foreach ($qry->result() as $row)
					{
							$saldo=$row->Saldo;	
					}
					$message="Saldo Anda Adalah Sebesar Rp.".$saldo;
					$textMessageBuilder = new TextMessageBuilder($message);
					$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
			}
			else if($pecah[0]=="PIN") {
				$pinid=$pecah[1];
				$anum=$pecah[2];
				$transid=$pecah[3];
				$idtranex=$this->tebakkode_m->cekTransId($transid);
				$pinidex=$this->tebakkode_m->cekPin($pinid,$anum);
				if($idtranex==0){
				$message="Id Transaksi Anda Tidak Diketahui";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);	
				}
				else if($pinidex==0){
				$message="Pin Anda Salah";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
				else {
					$date=date("Y-m-d");
					$qrys=$this->tebakkode_m->getTransaksi($transid);
					$trnum=uniqid();
					foreach ($qrys->result() as $row)
					{
							$accre=$row->Account_Number_re;
							$accde=$row->Account_Number_de;
							$prodid=$row->Product_id;
							$jml=$row->jumlah;
							$jenis=$row->jenis;
					}
					$qrypayer=$this->tebakkode_m->getPayer($anum);
					foreach ($qrypayer->result() as $rowpay)
					{
							$payid=$rowpay->Payer_Id;
							$saldo=$rowpay->Saldo;
							
					}
					$qryprice=$this->tebakkode_m->getPrice($prodid);
					foreach ($qryprice->result() as $rowprice)
					{
							$price=$rowprice->Sell_Price;
					}
					$qrydest=$this->tebakkode_m->getPayer($accde);
					foreach ($qrydest->result() as $rowdest)
					{
							$saldode=$rowdest->Saldo;
					}
					if($jenis=="TRF"){
					$this->tebakkode_m->saveTransTrf($trnum,$anum,$payid,$jml,$jenis,$date,'');
					$saldoakhir=$saldo-$jml;
					$saldoakhirde=$saldode+$jml;
					$this->tebakkode_m->updatePayer($anum,$saldoakhir);
					$this->tebakkode_m->updatePayer($accde,$saldoakhirde);
					$message="Transfer Anda Telah Selesai Di Proses , Terima Kasih";
					}
					else if($jenis=="BUY"){
					$total=$jml * $price;
					$this->tebakkode_m->saveTransBuy($trnum,$anum,$payid,$total,$jenis,$date,'','');	
					$this->tebakkode_m->saveTransDetail($trnum,$prodid,$jml,$total);
					$message="Pembelian Anda Telah Diproses Dengan Nomor Transaksi ".$trnum;
					}
					$textMessageBuilder = new TextMessageBuilder($message);
					$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
			}
			else if($pecah[0]=="BAYAR") {
				$jml=$pecah[1];
				$anum=$pecah[2];
				$kdtoko=$pecah[3];
				$pinid=$pecah[4];
				$kddiskon=$pecah[4];
				$kdtkidx=$this->tebakkode_m->cekToko($kdtoko);
				$pinidex=$this->tebakkode_m->cekPin($pinid,$anum);
				if($kdtkidx==0){
				$message="Account Toko Yang Anda Masukkan Tidak Terdaftar";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);	
				}
				else if($pinidex==0){
				$message="Pin Anda Salah";
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}
				else {
					$date=date("Y-m-d");
					$trnum=uniqid();
					$qrypayer=$this->tebakkode_m->getPayer($anum);
					foreach ($qrypayer->result() as $rowpay)
					{
							$payid=$rowpay->Payer_Id;
							$saldo=$rowpay->Saldo;
							
					}
					$diskonidx=$this->tebakkode_m->cekDiskon($kddiskon);
					if($diskonidx==0){
					$total=$jml;
					$saldoakhir=$saldo-$jml;
					$this->tebakkode_m->updatePayer($anum,$saldoakhir);
					$this->tebakkode_m->saveTransBuy($trnum,$anum,$payid,$total,$jenis,$date,'PAID',$kdtoko);	
					//$this->tebakkode_m->saveTransDetail($trnum,$prodid,$jml,$total);
					$message="Pembayaran Anda Telah Diproses Dengan Nomor Transaksi ".$trnum;
					}
					else {
						$qrydiskon=$this->tebakkode_m->getDiskon($kddiskon);
						foreach ($qrydiskon->result() as $rowdisk)
						{
								$diskonid=$rowpay->Kd_Diskon;
								$jmldsk=$rowpay->Jumlah_Diskon;	
						}
						$total=$jml-$jmldsk;
						$saldoakhir=$saldo-$total;
						$this->tebakkode_m->updatePayer($anum,$saldoakhir);
						$this->tebakkode_m->saveTransBuy($trnum,$anum,$payid,$total,$jenis,$date,'PAID',$kdtoko);	
						$message="Pembayaran Anda Telah Diproses Dengan Nomor Transaksi ".$trnum;
					}
					$textMessageBuilder = new TextMessageBuilder($message);
					$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				}	
			}
			else if($pecah[0]=="1"){
				$message = 'EPayment Ini Merupakan Line Application Yang Digunakan Untuk Melakukan Proses Transaksi Secara Elektronik Yang Meliputi :
					1. Transfer
					2. Pembelian Produk
					3. Pembayaran
					4. Top Up Saldo/Balance
					Untuk Menggunakan Fitur Diatas Anda Harus Terlebih Dahulu Memiliki Akun EPayment
					Akun Epayment Tersebut Nantinya Berisi Identitas Anda , Account Number , Pin Serta Saldo Awal Yang Anda Masukkan
					Untuk Proses Lainnya, Silahkan Anda Membalas Ke Line Apps Kami Sesuai Dengan Nomor Info 1-4';
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
			}
			else if($pecah[0]=="2"){
				$message = 'Untuk Melakukan Registrasi Silahkan Hubungi
				023020010

				Atau melalui Email
				epayment@gmail.com';
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
			}
			else if($pecah[0]=="3"){
				$message = 'Untuk Menggunakan Applikasi Ini Ada Beberapa Hal Yang Harus Anda Perhatikan, Yaitu
				Pada Saat Melakukan Proses Transaksi, Format Untuk Tiap Transaksi Berbeda Diantaranya Adalah Sebagai Berikut :
				1. Untuk Proses Transfer
					TRF AccountNumberAnda <spasi> AccountNumTujuan <spasi> Jumlah
				2. Untuk Proses Pembelian
					BUY AccountNum <spasi> ProductId <spasi> Jumlah
				3. Proses Pembayaran
					PAY AccountNum <spasi> Transacnum <spasi> Total
				4. Proses Top Up
				   TOPUP AccountNum <spasi> PIN <spasi> Jumlah';
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
			}
			else if($pecah[0]=="4"){
				$options1[] = new MessageTemplateActionBuilder("Klik Info Lanjut", "2");
				$buttonTemplate1 = new ButtonTemplateBuilder("Toko 1", "Menyediakan Kenikmatan Dalam Berkopi", "https://media-cdn.tripadvisor.com/media/photo-s/07/16/e5/46/tempatnya-nyaman-bgtwoody.jpg", $options1);
				$messageBuilder1 = new TemplateMessageBuilder("Okey", $buttonTemplate1);
				$options2[] = new MessageTemplateActionBuilder("Klik Info Lanjut", "3");
				$buttonTemplate2 = new ButtonTemplateBuilder("Toko 2", "Roti Enak Dan Bergizi", "https://media-cdn.tripadvisor.com/media/photo-s/08/82/a8/56/cakes.jpg", $options2);
				$messageBuilder2 = new TemplateMessageBuilder("Okey", $buttonTemplate2);
				$options3[] = new MessageTemplateActionBuilder("Klik Info Lanjut", "4");
				$buttonTemplate3 = new ButtonTemplateBuilder("Toko 3", "Nikmati Kesegaran Teh Alami", "https://kopertraveler.files.wordpress.com/2014/01/wpid-dsc_0409.jpg", $options3);
				$messageBuilder3 = new TemplateMessageBuilder("Okey", $buttonTemplate3);
				$options4[] = new MessageTemplateActionBuilder("Klik Info Lanjut", "5");
				$buttonTemplate4 = new ButtonTemplateBuilder("Toko 4", "Nikmati Oleh-Oleh Dengan Harga Terjangkau", "https://cdns.klimg.com/newshub.id/news/2016/06/25/67961/750x500-berkunjung-ke-5-lokasi-pusat-oleh-oleh-khas-malang-160625o.jpg", $options4);
				$messageBuilder4 = new TemplateMessageBuilder("Okey", $buttonTemplate4);
				$this->bot->pushMessage($event['source']['userId'], $messageBuilder1);
				$this->bot->pushMessage($event['source']['userId'], $messageBuilder2);
				$this->bot->pushMessage($event['source']['userId'], $messageBuilder3);
				$this->bot->pushMessage($event['source']['userId'], $messageBuilder4);
			}
			else if($pecah[0]=="MENU"){
				$menu=["Info Aplikasi","Info Registrasi","Info Perintah","Info Merchant"];
				for($i=1;$i<=4;$i++){
						$options[] = new MessageTemplateActionBuilder($menu[$i], $i);
				}
				$buttonTemplate = new ButtonTemplateBuilder("Menu Aplikasi", "Menu Utama Dari Aplikasi Epayment", "https://cdn.techinasia.com/wp-content/uploads/2015/04/epayment-vmoney-screenshot-720x467.png", $options);
				$messageBuilder = new TemplateMessageBuilder("Okey", $buttonTemplate);
				$this->bot->pushMessage($event['source']['userId'], $messageBuilder);
			}
			else {
				$message = 'Command Yang Anda Ketikkan Tidak Dikenali';
				$textMessageBuilder = new TextMessageBuilder($message);
				$this->bot->pushMessage($event['source']['userId'], $textMessageBuilder);
			}
	}
}
