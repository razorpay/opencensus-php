<header>
  <h1 align="center">Typescript Support</h1>
  <details>
    <summary align="center"><b>Changelog</b></summary>

| Version | Date         | Creators           | Reviewers | Remarks       |
| ------- | ------------ | ------------------ | --------- | ------------- |
| 1.0     | Jan 23, 2023 | `Bhaskar Mishra`   |           | Initial Draft |
| 1.1     | Jan 30, 2023 | `Ritesh Ganjewala` |           | Refactor      |

  </details>
</header>

### Introduction

Dashboard supports typescript integration and compilation out of the box now and all new features on Dashboard are recommended to be written on typescript.

You can find the typescript configuration [here](../web/tsconfig.json)

### Add Typescript support for a feature

Let's say you are creating a new page as a feature, you'd want to create a directory that will contain all the new code for that feature.

- Create a new `ExcitingNewFeature` directory. For this doc, we are assuming to have created the `js/merchant/views/ExcitingNewFeature` directory, but this path can be anywhere in the `web` folder.

* Go to `tsconfig.json` in the `web` directory, add the path to your directory to the `include` array -

  ```diff
  {
    //...
    "include": [
      "typings/**/*",
      "js/newAuth/signin/types.d.ts",
      "js/merchant/views/TermsAndCondition",
      "js/merchant/views/referral",
      "js/merchant/views/onboarding",
      "js/merchant/views/Settlements",
      "js/merchant/views/PartnerDashboard",
      "js/merchant/views/Navigator",
      "js/merchant/views/ApiKeysAndPlugins",
  +   "js/merchant/views/ExcitingNewFeature"
    ],
    //...
  }
  ```

  > **Note -** the path to your feature directory in `tsconfig.json` should be relative. Meaning it doesn't start with `web` because tsconfig.json itself resides in the web folder
