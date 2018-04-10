## Oriental Bank Netbanking Integration

**[Documentation][docs]**


[docs]: https://docs.google.com/document/d/1FpNqBr8-1RZSv2S2Y58c9x2OQYFv3zSNWZFhUSu5uOw

## Refund File Format

*  FileName => REFUND_NB_OBC_MERCHANTNAME_20151123.txt

*  Format => Delimited Text File, Field Delimiter (|)

*  Header: HOBCUTLPRFD|20151123 { Claim/Settlement Date }|PayeeID ( Allotted by Bank to Merchant)

* Field1 : PGI/MERCHANT Transaction Ref#
  
* Field2 : Refund Type ( R/C )

* Field3 : Refund Amount {Decimal Format}

* Field4 : Bank Transaction Ref#

* Field5 : Claim/Settlement Date { Refund Amount to be Adjusted against
which Date Settlement Amount / Must be same date for all records in the
File}

* Field6 : Original Transaction Amount

* Field7 : PGI/MERCHANT Unique Refund Ref#

* Footer : TOBCUTLPRFD|20151123 |{No. of Refunds } | { Total Refund Amount }

Sample

       "HOBCUTLPRFD|20180410|random_merchant_id
       9xXgM0N95MTKnE|R|500|9999999999|20180410|500|9xXgRbAekoVjc2
       9xXgOoDzqaVcyR|R|500|9999999999|20180410|500|9xXgS855lIEO6U
       9xXgQkyIjQAW6I|R|100|9999999999|20180410|500|9xXgSd8eW8y4bZ
       TOBCUTLPRFD|20180410|3|1100"

            

