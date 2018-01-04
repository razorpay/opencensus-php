# UPI/HDFC/Mindgate Gateway

**Tech**: Mindgate
**[Documentation][docs]**
**People**: Nemo

## Notes

- All the parameters passed are in a numeric array
- Formatting is "param1|param2|param3", so the API is order sensitive
- API could also be count sensitive, so we pad the paramaters with "NA", wherever we can
- Crypto is AES in ECB mode
- They do have a validate_vpa API that we haven't integrated completely yet
- The error mappings are not complete. Known issue
- Response Parsing is maintained in ResponseFields.php
- See the [docs][docs] about Request param ordering
- TODO: Move ACQUIRER entirely to Terminal


[docs]: https://drive.google.com/drive/u/0/folders/0B1MTSXtR53PfYldqNUIyLXlnSjA
