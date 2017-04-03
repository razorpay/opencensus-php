# UPI/ICICI Gateway

**Documentation**: https://drive.google.com/drive/u/1/folders/0B1MTSXtR53PfZ19rRFQySm8yQTg
**People**: Nemo / SHK

## Flow

- Authorize request includes a VPA
- We initiate a collect request against that VPA
- Checkout waits (response is returned)
- Customer authorizes the payment
- We receive a callback, authorize the payment
- Checkout notifies the merchant about the payment
- Verify is a separate API call

## UPI Entity

- action: Uses `$this->action`
- amount: Amount given to us via gateway response (2 decimal places)
- bank: `icici`
- contact: Filled by the bank response
- name: Filled by the bank response
- received: Filled as soon as we get a response
- gateway_merchant_id: same as config->$mode-merchant-id
- gateway_payment_id: Filled using the Receiver Registration Number (Bank RRN)
- email: Filled using customer information from checkout
- status_code: Response code from Bank
- vpa: Filled when customer makes payment, asserted to be same when we get response

## Refund

- Make sure you are on atleast v2.3 of the doc (which has the refunds API)

## Weirdness

- Payment remains in `created`, not `authorized`, so flow is async
- Request/Responses are encrypted using RSA, which means the input
  is very often "strings", instead of arrays.
- the authorize method returns true, because we do not have a request to redirect to
- the icici server only encrypts responses sometimes. (Take a look at mock server)
- submerchantID is expected to be maxinmum numeric 10 characters, so we always send 1234


## Testing

- Download and install the APK from the Google Drive link above
- You will still need the environment variables to raise the collect request
- PIN=1234 (For the iMobile App)
- Issue a collect request via obscure.php to `vishnu@icici`
- Accept the collect request
- Verify->Authorize->Capture the payment
