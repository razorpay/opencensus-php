**IMPORTANT**

For reviewing PR, please tag @dashboard-pr-reviewers **and** your respective pod's PR reviewers on [#payments-dashboard-pr-reviews](https://razorpay.slack.com/archives/C01N2CDSB7H) channel

***For PR Reviewers: In case of a UI change, Please don't approve PR if there is no screenshot or video attached.***

---

**Description**

1. What is this PR about?

2. Write individual changes in points

3. Add Screenshot/Video covering what changed? (Mandatory If There Is An UI Change)

---

**List of impacted parts in dashboard**

- Eg: modules, features, whole dashboard, roles-permissions, etc.
- Eg: Small/Big functionality in invoices
- Eg: UI of entire invoices page

---

**Responsiveness**

- [ ] Added new components? Make sure to add the video recording of your component, to confirm your designs are fully responsive for all the screen sizes(mobile, tablet and desktop).

---

**Dependencies**

- [ ] Add any related PRs? Like api, static, etc.

---

**Check List**

Make sure to check the checkboxes which are necessary for your PR.

- [ ] Responsive design is handled if there are new components/UI changes?
- [ ] Add Jira ID(s) in PR title or in the description?
- [ ] Any Screenshots (mobile & desktop) required for PR?
- [ ] Have you verified if your changes are working fine on Devstack env as well?
- [ ] Ensure that all API calls are handled gracefully
- [ ] Have you adhered to [Dashboard PR review guidelines](https://docs.google.com/document/d/1dY5r9zKfUokAHyRHH-6MNs9d1lFoTSSv1FEuts19eX8/edit#heading=h.ds6jxd1qlcl)?
- [ ] Test Case Document URL. (Please paste test case document link here....)
- Note :- Please follow the below points while attaching test cases document link:
  - If label `Tested` is added then test cases document URL is mandatory.
  - Link added should be a valid URL and accessible throughout the org.
  - If the branch name contains hotfix / revert by default the BVT workflow check will pass.

---

**Note**

If your changes are regarding any home page widgets like announcements, banners, slider etc [growth assets](https://docs.google.com/document/d/1jj3LZRgDrd8AQCTDZ6r7uAIbLPLu_PaaBuxRLcngmjg/edit#), please call out the Platform Growth team (@dashboard-widgets-team in Slack). The changes on these UI elements should be done through CampaignHQ. Please check the [runbook](https://docs.google.com/document/d/1BFpskX9BFchgYo1eueUmSfJ7hy-4jbkaDDDsqQCoZuk/edit?usp=sharing) for more information.