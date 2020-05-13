<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Razorpay Custom Checkout</title>
    <script>
        Razorpay = {
            config: {
                api: "https://zeta5-api.stage.razorpay.in/"
            }
        };
    </script>
    <script
        type="text/javascript"
        src="https://checkout.razorpay.com/v1/razorpay.js"
    ></script>
</head>
<body>
<button>Pay with Razorpay</button>

<script>
    const razorpay = new Razorpay({
        key: "rzp_test_ZAxCg9TB9J6ifu",
        redirect: true
        // callback_url: "https://google.com"
    });

    document.querySelector("button").addEventListener("click", event => {
        event.preventDefault();

        razorpay.createPayment({
            amount: 100,
            contact: "+919988776655",
            email: "void@razorpay.com",
            method: "wallet",
            wallet: "phonepeswitch"
        });

        razorpay.on("payment.success", function(resp) {
            if (resp.razorpay_payment_id) {
                alert(resp.razorpay_payment_id);
            }

            if (resp.razorpay_order_id) {
                alert(resp.razorpay_order_id);
            }

            if (resp.razorpay_signature) {
                alert(resp.razorpay_signature);
            }
        });

        razorpay.on("payment.error", function(resp) {
            alert(resp.error.description);
        });
    });
</script>
</body>
</html>
