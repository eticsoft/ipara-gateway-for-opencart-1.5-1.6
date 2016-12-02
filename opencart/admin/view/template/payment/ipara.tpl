<?php echo $header; ?>
<div id="content">

	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
	        - <a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>
    <div class="box">
        <div class="heading">
			<h1>iPara Kredi Kartı Ödeme Modülü</h1>
			<div class="heading">
				<h1><img src="view/image/payment.png" alt="" /> <?php echo $heading_title; ?></h1>
				<div class="buttons"><a onclick="$('#form').submit();" class="button" id="saveKeys"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
			</div>
		</div>

        <div class="content">

			<?php if (!$ipara_registered OR $ipara_registered == null) { ?>
	            <br/><hr/>
				<form action="<?php echo $action ?>" method="post" class="form" id="form">
					<div style="background: rgba(255, 0, 0, 0.07); color: #333;font-size: 15px;padding: 15px;min-height: 380px;">
						<h2>Kullanım Şartları </h2>
						<ul>
							<li>iPara modülü Aypara Ödeme Kuruluşu A.Ş tarafından GPL lisansı ile açık kaynaklı ve ücretsiz sunulmaktadır. <b>Satılamaz.</b></li>
							<li>iPara modülü Aypara Ödeme Kuruluşu A.Ş 'nin sağladığı servisleri kullanmak için geliştirilmiştir. Başka amaçla kullanılamaz.</li>
							<li>Uluslararası güvenlik standartlarında kredi kartı bilgilerine erişim veya bilgilerin kayıt edilmesi yasaktır. Bu modül orijinal kaynak kodlarıyla müşterilerinizin kredi kartı bilgilerini asla kaydetmez. Kaynak kodlarını bu kurallara uygun tutmak sizin sorumluluğunuzdadır.</li>
							<li>Bazı mağaza bilgileriniz (mağaza eposta, mağaza adresi ve versiyon) geliştirici teknik destek ve bilgilendirme sistemine otomatik kayıt edilecek ve bu bilgiler önemli bildirimler ile güncellemelerden haberdar olmanız için kullanılacaktır</li>
							<li>Mağaza bilgileriniz asla 3. kişi ve kurumlar ile paylaşılmaz.</li>
						</ul>
						<hr>
						<input type="checkbox" value="1" name="confirm_ipara_register" checked><br/>
							<label for="confirm_ipara">Kullanım şartlarını kabul ediyorum</label>
							<br>
							<a onclick="$('#registeripara').submit();" class="button">Mağazamı Kaydet ve Başla</a>
					</div>
				</form>
			<?php } else { ?>
				<form action="<?php echo $_SERVER['REQUEST_URI']?>" method="post" id="form">
				<?php if ($error_warning) { ?>
					<div class="warning"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
					</div>
				<?php } ?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
					</div>

					<div class="panel-body">
						<ul class="nav nav-tabs" id="tabs">
							<li class="active"><a href="#tab-ipara_settings" data-toggle="tab">Genel Ayarlar</a></li>
							<li><a href="#tab-ipara_rates" data-toggle="tab">Taksit Oranları</a></li>
							<li><a href="#tab-ipara_about" data-toggle="tab">iPara Hakkında</a></li>
							<li><a href="#tab-ipara_help" data-toggle="tab">Yardım&Teknik destek</a></li>
						</ul>
						<div class="tab-content">
							<div class="tab-pane active" id="tab-ipara_settings">
                <table class="form">
								<tr>
									<td>iPara Açık Anahtar</td>
									<td><input type="text" name="ipara_publickey" value="<?php echo $ipara_publickey; ?>" placeholder="X1Y2Z3Q4..." id="ipara_publickey" class="form-control" /></td>
								</tr>
								<tr>
									<td>iPara Kapalı Anahtar</td>
									<td><input type="text" name="ipara_privatekey" value="<?php echo $ipara_privatekey; ?>" placeholder="X1Y2Z3Q4A5B6C7Z8..." id="ipara_privatekey" class="form-control" /></td>
								</tr>
								<tr>
									<td>Taksit Seçenekleri Sekmesi</td>
									<td>
										<select name="ipara_ins_tab" id="input-ipara_ins_tab" class="form-control">              
											<option value="on">Göster</option>
											<option value="off" <?php if ($ipara_ins_tab == 'off') { ?>selected="selected"<?php } ?>>Gizle</option>
										</select>
									</td>
								</tr>
								<tr>
									<td>3D Secure Yönetimi</td>
									<td>
										<select name="ipara_3d_mode" id="input-ipara_3d_mode" class="form-control">              
											<option value="auto" <?php if ($ipara_3d_mode == 'auto') { ?>selected="selected"<?php } ?>>Otomatik - iPara webservisi karar versin (Önerilen)</option>
											<option value="on" <?php if ($ipara_3d_mode == 'on') { ?>selected="selected"<?php } ?>>Tüm ödemeler 3DS ile yapılsın (SMS ile şifre gönder) </option>
											<option value="off" <?php if ($ipara_3d_mode == 'off') { ?>selected="selected"<?php } ?>>Asla 3DS kullanma (Hızlı tek sayfada ödeme)</option>
										</select>
									</td>
								</tr>
								<tr>
									<td>Modül durumu</td>
									<td>
										<select name="ipara_status" id="input-status" class="form-control">                
											<option value="1" selected="selected">Aktif</option>
											<option value="0" <?php if (!$ipara_status) { ?> checked="checked" <?php } ?> >Pasif</option>
										</select>
									</td>
								</tr>
								<tr>
									<td>Sipariş durumu</td>
									<td>
											<select name="ipara_order_status_id" id="input-order-status" class="form-control">
												<?php foreach ($order_statuses as $order_status) { ?>
													<?php if ($order_status['order_status_id'] == $ipara_order_status_id) { ?>
														<option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
													<?php } else { ?>
														<option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
													<?php } ?>
												<?php } ?>
											</select>									</td>
								</tr>
								</table>

									<input type="hidden" name="ipara_submit" value="1"/>
							</div>
							<div class="tab-pane" id="tab-ipara_rates">
	                            <div class="form-group">
									<?php echo $ipara_rates_table ?>
	                            </div>
	                            <input type="hidden" name="ipara_rates_submit" value="1"/>
	                            <input type="hidden" name="ipara_registered" value="ok"/>

							</div>
							<div class="tab-pane" id="tab-ipara_about">
							<hr/>
							<table width="100%">
								<tr>
									<td>
																				<h5>iPara size aşağıdaki avantajları Sağlar</h5>
											<dl>
												<dt>· Onlarca banka kartına taksitli alışveriş sunar</dt>
												<dd>7 bankanın kartlarına 0-12 taksit imkanı sunar. Visa®, Mastercard®, American Express® logosu taşıyan tüm kartlardan ödeme alır</dd>

												<dt>· Bankalara göre çok düşük komisyon oranı ödersiniz. </dt>
												<dd>Bankalara teker teker başvuru yapıp bir sürü ücret ödemezsiniz. Ayrıca komisyon oranları oldukça caziptir.</dd>

												<dt>· Gelişmiş güvenlik sistemi sağlar.</dt>
												<dd>Bir çok güvenlik duvarı ve şifreleme algoritması ile sizi dolandırıcılara karşı korur.</dd>

												<dt>· Tek başvuruyla 7 bankanın sanal posuna sahip olursunuz.</dt>
												<dd>0-12 taksit seçeneği s Word Bonus Axess CarFinans AsyaCard Maximum ve Paraf </dd>
											</dl>
											<img src="../catalog/view/theme/default/image/ipara/available_cards.png" width="303" id="payment-logo">
									</td>
									<td align="center" width="49%">					
									<img src="../catalog/view/theme/default/image/ipara/ipara_logo.png" width="292" id="payment-logo">
											<div class="col-xs-6 col-md-5 text-center">
												<h4>Online ödeme çözümü</h4>
												<h4>Hızlı, Güvenli, Kolay</h4>
											</div>
											<div class="col-xs-12 col-md-5 text-center">
												<a href="https://ipara.com" class="button" id="create-account-btn">iPara'ya başvuru yap !</a><br>
												Hesabınız zaten var mı ?<a href="https://ipara.com"> Giriş</a>
											</div>
									</td>
								</tr>
							</table>
								<div class="panel">
									<div class="col-sm-2 text-center">
										<img src="../catalog/view/theme/default/image/ipara/eticsoft_logo.png" class="col-sm-12 text-center" id="payment-logo">
									</div>
									<div class="col-sm-7 text-center">
										<p class="text-muted">
										<i class="icon icon-info-circle"></i>Bu modül EticSoft R&amp;D lab tarafından AyPara Aypara Ödeme Kuruluşu A.Ş. için geliştirilmiştir.

										</p>
									</div>
									<div class="col-sm-3 text-center">

										<p>
											<a href="#" onclick="javascript:return false;"><i class="icon icon-file"></i>Kullanım Klavuzu</a>
										</p>
									</div>
									<hr>		

								</div>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</form>
<?php } ?>
<style>
    #content .tab-pane:first-child .panel {
        border-top-left-radius: 0;
    }

    .ipara-header .text-branded,
    .ipara-content .text-branded {
        color: #00aff0;
    }

    .ipara-header h4,
    .ipara-content h4,
    .ipara-content h5 {
        margin: 2px 0;
        color: #00aff0;
        font-size: 1.8em;
    }

    .ipara-header h4 {
        margin-top: 5px;
    }

    .ipara-header .col-md-6 {
        margin-top: 18px;
    }

    .ipara-content h4 {
        margin-bottom: 10px;
    }

    .ipara-content h5 {
        font-size: 1.4em;
        margin-bottom: 10px;
    }

    .ipara-content h6 {
        font-size: 1.3em;
        margin: 1px 0 4px 0;
    }

    .ipara-header > .col-md-4 {
        height: 65px;
        vertical-align: middle;
        border-left: 1px solid #ddd;
    }

    .ipara-header > .col-md-4:first-child {
        border-left: none;
    }

    .ipara-header #create-account-btn {
        margin-top: 14px;
    }

    .ipara-content dd + dt {
        margin-top: 5px;
    }

    .ipara-content ul {
        padding-left: 15px;
    }

    .ipara-content .ul-spaced li {
        margin-bottom: 5px;
    }
    table.ipara_table {
        width:90%;
        margin:auto;
    }
    table.ipara_table td,th {
        width: 60px;
        margin:0px;
        padding:2px;
    }
    table.ipara_table input[type="number"] {
        width:50px;
    }
</style>
<?php echo $footer; ?>
