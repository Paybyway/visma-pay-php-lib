<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

/*
	If you are not using composer, use the following file to load all the class files instead of composer's autoload.php above
	require dirname(__FILE__) . '/../lib/visma_pay_loader.php';
*/

$vismaPay = new VismaPay\VismaPay('api_key', 'private_key');

$payment_return = '';

if(isset($_GET['action']))
{
	if($_GET['action'] == 'auth-payment')
	{
		$serverPort = (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != 80 &&  $_SERVER['SERVER_PORT'] != 433)) ? ':' . $_SERVER['SERVER_PORT'] : ''; 

		$returnUrl = strstr("http" . (!empty($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'] . $serverPort . $_SERVER['REQUEST_URI'], '?', true)."?return-from-pay-page";

		$method = isset($_GET['method']) ? $_GET['method'] : '';

		$vismaPay->addCharge(array(
			'order_number' => 'example_payment_' . time(),
			'amount' => 2000,
			'currency' => 'EUR'
		));

		$vismaPay->addCustomer(array(
			'firstname' => 'Example', 
			'lastname' => 'Testaaja', 
			'address_street' => 'Testaddress 1',
			'address_city' => 'Testlandia',
			'address_zip' => '12345'
		));

		$vismaPay->addProduct(array(
			'id' => 'product-id-123', 
			'title' => 'Product 1',
			'count' => 1,
			'pretax_price' => 2000,
			'tax' => 1,
			'price' => 2000,
			'type' => 1
		));

		if($method === 'iframe')
			$returnUrl .= '&iframe';

		$paymentMethod = array(
			'return_url' => $returnUrl,
			'notify_url' => $returnUrl,
			'lang' => 'fi'
		);

		if($method === 'embedded')
			$paymentMethod['type'] = 'embedded';
		else
			$paymentMethod['type'] = 'e-payment';

		if(isset($_GET['selected']))
		{
			$paymentMethod['selected'] = array(strip_tags($_GET['selected']));
		}

		$vismaPay->addPaymentMethod($paymentMethod);

		try 
		{
			$result = $vismaPay->createCharge();

			if($result->result == 0)
			{
				if($method === 'iframe')
				{
					header('Cache-Control: no-cache');
					echo json_encode(array(
						'url' => $vismaPay::API_URL . '/token/' . $result->token
					));
				}
				else if($method === 'embedded')
				{
					echo json_encode(array(
						'token' => $result->token
					));
				}
				else
				{
					header('Location: ' . $vismaPay::API_URL . '/token/' . $result->token);
				}
			}
			else
			{
				$error_msg = 'Unable to create a payment. ';

				if(isset($result->errors) && !empty($result->errors))
				{
					$error_msg .= 'Validation errors: ' . print_r($result->errors, true);
				}
				else
				{
					$error_msg .= 'Please check that api key and private key are correct.';
				}

				exit($error_msg);
			}
		}
		catch(VismaPay\VismaPayException $e)
		{
			exit('Got the following exception: ' . $e->getMessage());
		}
	}

	exit();
}
else if(isset($_GET['return-from-pay-page']))
{
	try
	{
		$result = $vismaPay->checkReturn($_GET);

		if($result->RETURN_CODE == 0)
		{
			$payment_return = 'Payment succeeded';
		}
		else
		{
			$payment_return = 'Payment failed (RETURN_CODE: ' . $result->RETURN_CODE . ')';
		}
	}
	catch(VismaPay\VismaPayException $e)
	{
		exit('Got the following exception: ' . $e->getMessage());
	}
}

try
{

	$merchantPaymentMethods = $vismaPay->getMerchantPaymentMethods();

	if($merchantPaymentMethods->result != 0)
	{
		exit('Unable to get the payment methods for the merchant. Please check that api key and private key are correct.');
	}
}
catch(VismaPay\VismaPayException $e)
{
	exit('Got the following exception: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Visma Pay PHP Library Example</title>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.0/jquery.min.js"></script>
		<style type="text/css">
			a, a:hover, a:focus
			{
				text-decoration: none;
			}
			#overlay 
			{
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background-color: #000;
				filter:alpha(opacity=50);
				-moz-opacity:0.5;
				-khtml-opacity: 0.5;
				opacity: 0.5;
				z-index: 1;
			}

			#payment_frame
			{
				height : 650px;
				width : 500px;
				position: absolute;
				z-index: 2;
				margin-left: -250px;
				left: 50%;
				top: 20px;
			}
		</style>	
	</head>
	<body>
		<div class="container">
			<?php if($payment_return): ?>
				<div class="row">
					<div class="col-md-12">
						<div class="alert alert-success" role="alert"><?=$payment_return;?>, <a target="_top" href="index.php">start again</a></div>
					</div>
				</div>
			<?php endif; ?>
			<div class="row" id="mainpage">
				<div class="col-md-12">
					<h1>Visma Pay PHP Library Example</h1>
					<div class="card-payment-result text-muted"></div>
					<hr>
					<h2>Pay by button</h2>
					<?php foreach ($merchantPaymentMethods->payment_methods as $pm): ?>
						<a class="img" href="?action=auth-payment&method=button&selected=<?=$pm->selected_value?>">
							<img alt="<?= $pm->name ?>" src="<?= $pm->img ?>">
						</a>
					<?php endforeach; ?>
					<hr>
					<h2>Go to pay page</h2>
					<a class="btn btn-default" href="index.php?action=auth-payment&method=button">Go to pay page</a>
					<hr>
					<h2>Open minified card form in iframe</h2>
					<a class="btn btn-default" href="#" id="iframe">Open iframe</a>
					<hr>
					<h2>Embedded iframe card form</h2>
					<iframe frameBorder="0" scrolling="no" id="pf-cc-iframe" height="220px" style="width:100%" src="https://www.vismapay.com/e-payments/embedded_card_form?lang=en"></iframe>
					<a class="btn btn-default" href="#" id="inline-form">Pay with inline card form</a>
				</div>
			</div>
		</div>
	<script>
		window.addEventListener('message', function(event) {
			var data = JSON.parse(event.data)

			if(data.valid)
			{
				var initEmbeddedPayment = $.get("?action=auth-payment&method=embedded")

				initEmbeddedPayment.done(function(data) {
					var response
					try
					{
						response = $.parseJSON(data)
					}
					catch(err)
					{
						alert('Unable to initialize embedded card payment. Please check that api key and private key are correct.')
						return
					}

					var payMessage = {
						action: 'pay',
						token: response.token
					}

					document.getElementById('pf-cc-iframe').contentWindow.postMessage(
						JSON.stringify(payMessage),
						'https://www.vismapay.com/'
					)
				})
			}
		});

		// Embedded iframe card form
		$("#inline-form").click(function(e) {
			e.preventDefault()

			var validateMessage = {
				action: "validate"
			}

			document.getElementById('pf-cc-iframe').contentWindow.postMessage(
				JSON.stringify(validateMessage), 
				'https://www.vismapay.com/'
			)
		})

		// Open minified card form in iframe
		var card_payment_result = $('.card-payment-result')
		$("#iframe").click(function(e)
		{
			e.preventDefault()
			var initPayment = $.get("?action=auth-payment&method=iframe")
			initPayment.done(function(data) {
				var response
				try
				{
					response = $.parseJSON(data)
				}
				catch(err)
				{
					card_payment_result.html('Unable to create card payment. Please check that api key and private key are correct.')
					alert('Unable to create card payment. Please check that api key and private key are correct.')
					return
				}
				var overlay = $('<div id="overlay"></div>').appendTo(document.body);
				$('<iframe>', {
					src: response.url+"?minified",
					id:  'payment_frame',
					frameborder: 0,
					scrolling: 'no'
				}).appendTo(document.body);
			})
		})

		//if in iframe, prevent inception
		if(window.self !== window.top)
			$("#mainpage").hide();
		
	</script>
	</body>
</html>
