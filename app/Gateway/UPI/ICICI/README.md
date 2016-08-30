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

## Weirdness

- Payment remains in `created`, not `authorized`, so flow is async
- Request/Responses are encrypted using RSA, which means the input
  is very often "strings", instead of arrays.
