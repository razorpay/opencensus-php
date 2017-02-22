# User Registration

We have a lot of attributes we can accept in our signup form. They have different
actions associated with them. The primary 3 are:

- invitation: The invitation token. Used in the signup form to indicate that the user is accepting an invite to join a merchant team
- referral: How to tag the merchant. This is a referral code that we can use to track the source of signup
- business_name: If this is present, we consider it to be a merchant registration and handle it accordingly.

The following shows the compatibility of each:

| Registration/Field | invitation | referral | business_name |
|--------------------|------------|----------|---------------|
| Merchant 			 | not_allowed| optional | required  	 |
| User 				 | required   | rejected | rejected      |

We decide on which branch to take using the business_name attribute,
but since the user creation comes first, we use the invitation code
before that (if present). As such, if we find a business_name,
we have an assert to make sure that both invitation and business_name
are not being used together.

Note that referral could still be sent during an invitation user registration
process, and will just be silently ignored since we won't try merchant
registration at all.

# Aggregator Model

We have an aggregator model using which a merchant can register other merchants under him and handle the dashboard on their behalf. In this case, the sub-merchant has a separate merchant account but its default owner is the user account of the main merchant. The email id of the sub-merchant is same as main merchant's by default but a new email can be optionally provided. We are now introducing an option to give the sub-merchant with different email his own user account based on that email. When this option is availed, the sub-merchant has two owners, self and the main merchant's user.
