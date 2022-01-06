# api/e2e

This directory is used to specify integration tests for modules still in api. There exists an example test. It uses [goutils/itf](https://github.com/razorpay/goutils/tree/master/itf#readme).

We recommend using goutils/itf for test specification primarily because api is being decomposed into go microservices over the next 1-2 years. If we write integration tests in php now, will have to first move from java(razorpay/roast) to php and then again to go. It will be inefficient. But, yes, we still have the option open to use either.

See [this guide](https://docs.google.com/document/d/1f3UtAu0evddfGXjAj3TpXRkiaaIsTM-83np1jv2h9X8) for more information and reference usage by other services.

Please ping on #platform-dev-productivity in case of any queries.

Thank you!
