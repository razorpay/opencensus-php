# IDFC Gateway

Current Status:

1. 7 APIs implemented
2. Adding remaining APIs is easy (just define message format)

Work to be done:

1. Figure out API sequence order
2. Convert UpiController and Core to include multiple backends
3. Decide between the gateways in the upi/Core somehow
4. Figure out mapping between which all APIs to call at IDFC end
5. Figure out how the ONUS thing works


## List of APIs

1. Add Bank Account API
2. Balance Enquiry API
3. Change PIN API
4. Check Transaction Status API
5. Generate Bank OTP API
6. Generate OTP
7. Generate Virtual Address API
8. List Bank Account API
9. List Public Keys API
10. Mobile Bank Registration API
11. New Profile Registration API
12. Register Account API
13. Remove Registered Bank Account API
14. Remove Registered Virtual Address API
15. Send Money API
16. Transaction List API
17. Update Registered Profile API
18. Update Registered Virtual Address API
19. View Registered Accounts API
20. View Registered Virtual Address API
21. View Registered Profile API
22. Account Preference API
23. Collect Money API
24. Confirm Collect Money API
25. Pending Approval API
26. Register FeedBack API
27. Generate Merchant DEK Key API
28. Corporate / Merchant Registration API
29. Corporate / Merchant Profile View API
30. Check Available Virtual Address API
31. Validate Virtual Address API

**Order**: [27 -> 6] -> 9 -> 30 -> 11 -> 1 -> 8 -> 12
