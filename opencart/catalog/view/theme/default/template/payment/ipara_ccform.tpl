<?php echo $header; ?>
<?php echo $column_left; ?>
<?php echo $column_right; ?>
<link rel="stylesheet" type="text/css" href="catalog/view/theme/default/stylesheet/ipara_form.css" />
<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
<script src="catalog/view/javascript/ipara/jquery.card.js"></script>
<script src="catalog/view/javascript/ipara/jquery.payment.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>


<div class="container" style="max-width: 980px;"><?php echo $content_top; ?>


<section>
	
    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <h2>Kredi Kartı ile Güvenli Ödeme</h2>
        </div>
         <div class="col-xs-12 col-sm-6">
                    Bu sayfada kredi kartınız ile güvenli ödeme yapabilirsiniz.<br/>
                    3D güvenlik için telefonunuza SMS gönderilebilir. 
         </div>
    </div>

   <?php if($error_message) { ?>
		<div class="row">
            <div class="alert alert-danger" id="errDiv">
                Ödemeniz yapılamadı. Bankanızın cevabı: <br/> 
                <b><?php echo $error_message; ?></b><br/>
                Lütfen formu kontrol edip yeniden deneyiniz.
            </div>
        </div>
    <?php } ?>
    <hr/>
</section>
<form novalidate autocomplete="on" method="POST" id="cc_form" action="<?php echo $form_link ?>">

    <div class="row">
        <div class="col-xs-12 col-sm-6">
            <table id="cc_form_table">
                <tr>
                    <td>
                    Kart No <br/>
                <input type="text" id="cc_number" name="cc_number" class="cc_input" placeholder="•••• •••• •••• ••••"/>
                </td>
                <td>
                    Kart son kullanım tarihi<br/>
                <input type="text" id="cc_expiry" name="cc_expiry" class="cc_input" placeholder="AA/YY"/>
                </td>
                </tr>
                <tr>
                    <td>
                    Güvenlik kodu (kartın arka yüzünde)<br/>
                <input type="text" id="cc_cvc" name="cc_cvc" class="cc_input" placeholder="•••"/>
                </td>
                <td> Kart üzerindeki isim<br/>
                <input type="text" id="cc_name" name="cc_name" class="cc_input" placeholder="Ad Soyad"/>
                </td>
                </tr>
            </table>
            <hr/>
            <div class="pull-right" id="cc_validation">Lütfen formu kontrol ediniz</div>
            <input type="hidden" name="cc_form_key" value="<?php echo $cc_form_key; ?>"/>
            <button type="submit" id="cc_form_submit" class="btn btn-lg btn-primary">Ödemeyi Tamamla</button>

        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="card-wrapper"></div>
        </div>
    </div>
	
    <div class="row">
        <div class="col-xs-12 col-sm-12">
            <hr/>
            <div class="form-group">
                <table id="cc_table" class="table-responsive">
                    <thead>
                        <tr>
                            <th class="empty">
                            </th>
                            <?php foreach ($rates as $bank => $rate) { ?>
                                <th colspan="2"><img src="<?php echo HTTPS_SERVER ?>catalog/view/theme/default/image/ipara/banks/<?php echo $bank; ?>.png" width="100px" height="22px" /></th>
                            <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="empty">
                            </td>
                            <?php foreach ($rates as $bank => $rate) { ?>
                                <td>Aylık</td>
                                <td>Toplam</td>
                            <?php } ?>
                        </tr>
                        <?php for ($ins=1; $ins < 10 ; $ins++){ ?>
                            <tr>
                                <td>
                                    <?php if ($ins == 1){ ?>
                            <input type="radio" name="cc_installment" id="cc_installment" class="input-lg form-control" checked value="<?php echo $ins; ?>"/>
                            <label for="cc_installment" class="control-label">Tek çekim</label>
                        <?php } else { ?>
                            <input type="radio" name="cc_installment" id="cc_installment" class="input-lg form-control" value="<?php echo $ins; ?>"/>
                            <label for="cc_installment" class="control-label"><?php echo $ins ?> Taksit</label>
                        <?php } ?>
                        </td>
						<?php foreach ($rates as $bank => $rate) { ?>
						<td><?php echo $rates[$bank]['installments'][$ins]['monthly']; ?></td>
						<td><?php echo $rates[$bank]['installments'][$ins]['total']; ?></td>
						<?php } ?>
                        </tr>
                            <?php } ?>
                    </tbody>
                </table>               
            </div>
        </div>
    </div>
</form> 


<script>
    $('form#cc_form').card({
        // a selector or DOM element for the form where users will
        // be entering their information
        form: 'form#cc_form', // *required*
        // a selector or DOM element for the container
        // where you want the card to appear
		formSelectors: {
			numberInput: 'input#cc_number', // optional — default input[name="number"]
			expiryInput: 'input#cc_expiry', // optional — default input[name="expiry"]
			cvcInput: 'input#cc_cvc', // optional — default input[name="cvc"]
			nameInput: 'input#cc_name' // optional - defaults input[name="name"]
		},
		placeholders: {
		  number: '&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;',
		  cvc: '&bull;&bull;&bull;',
		  expiry: 'AA/YY',
		  name: 'ADIM SOYADIM'
		},
		messages: {
            monthYear: 'mm/yy' // optional - default 'month/year'
        },
        container: '.card-wrapper', // *required*
        width: "100%",
        formatting: true, // optional - default true
        // Default placeholders for rendered fields - optional
        // if true, will log helpful messages for setting up Card
        debug: true // optional - default false
    });

	$('table#cc_table tr').click(function() {
		$(this).find('td input:radio').prop('checked', true);
	})

    jQuery(function ($) {
        $('table#cc_form_table').removeClass('error success');
        $('input#cc_number').payment('formatCardNumber');
        $('input#cc_expiry').payment('formatCardExpiry');
        $('input#cc_cvc').payment('formatCardCVC');
        $("#cc_form_submit").attr("disabled", true);

        $('.cc_input').bind('keypress keyup keydown focus', function (e) {
            $(this).removeClass('error');
            $("#cc_form_submit").attr("disabled", true);
            var hasError = false;
            var cardType = $.payment.cardType($('input#cc_number').val());


            if (!$.payment.validateCardNumber($('input#cc_number').val())) {
                $('input#cc_number').addClass('error');
                hasError = 'number';
            }
            if (!$.payment.validateCardExpiry($('input#cc_expiry').payment('cardExpiryVal'))) {
                $('input#cc_expiry').addClass('error');
                hasError = 'expiry';
            }
            if (!$.payment.validateCardCVC($('input#cc_cvc').val(), cardType)) {
                $('input#cc_cvc').addClass('error');
                hasError = 'cvc';
            }
            if ($('input#cc_name').val().length < 3) {
                $('input#cc_name').addClass('error');
                hasError = 'name';
            }

            if (hasError === false) {
//                console.log(hasError);
                $("#cc_form_submit").removeAttr("disabled");
                $("#cc_validation").hide();
            }
            else {
                $("#cc_validation").show();
                $("#cc_form_submit").attr("disabled", true);
                $('table#cc_form_table').addClass('error');
            }
        });
		$('.cc_input').keypress();
    });
</script>
<?php echo $footer; ?>