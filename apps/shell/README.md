# React TypeScript Node App <!-- omit in toc -->

- [🧑🏻‍💻 Development](#-development)
  - [⚙️ Running Project Scripts](#️-running-project-scripts)
  - [🖌 Git Commit Guidelines](#-git-commit-guidelines)
  - [🔐 Environment Variables and Secrets](#-environment-variables-and-secrets)
  - [🛠 Customization](#-customization)
- [🚀 Deployment](#-deployment)
  - [🧰 CI/CD Setup](#-cicd-setup)
  - [🔢 Automated Versioning and Changelog](#-automated-versioning-and-changelog)
- [📊 Monitoring](#-monitoring)
  - [🐞 Error Monitoring](#-error-monitoring)
  - [📈 Performance Monitoring](#-performance-monitoring)
- [📖 Learn More](#-learn-more)

## 🧑🏻‍💻 Development

### ⚙️ Running Project Scripts

This project comes with a set of scripts that can be used to run your app.

Learn more about them here in our detailed guide: [Running Project Scripts](https://universe.razorpay.com/docs/running-project-scripts)

### 🖌 Git Commit Guidelines

We have very precise rules over how our git commit messages can be formatted. This leads to **more readable messages** that are easy to follow when looking through the **project history**.

Learn more about it here in our detailed guide: [Git Commit Guidelines](https://universe.razorpay.com/docs/git-commit-guidelines)

### 🔐 Environment Variables and Secrets

This project has support for build time environment variables.

Learn more about it here in our detailed guide: [Environment Variables and Secrets](https://universe.razorpay.com/docs/environment-variables-and-secrets).

### 🛠 Customization

This project comes with a set of pre-configured configurations for tools like `webpack`, `eslint`, `secretlint`, `dangerJS`, `lint-staged`, `babel`, etc. You can extend and customize the project configurations as per your needs.

Learn more about it here in our detailed guide: [Customization of the project configurations](https://universe.razorpay.com/docs/customizing-projects-configs).

## 🚀 Deployment

### 🧰 CI/CD Setup

This project comes with various CI/CD workflows that can be used to automate the build and deployment process.

Learn more about it here in our detailed guide: [CI/CD Setup](https://universe.razorpay.com/docs/ci-cd-setup).

### 🔢 Automated Versioning and Changelog

This project comes baked in with Automated Versioning support. To handle automated versioning we are using [changesets](https://github.com/changesets/changesets).

Learn more about it here in our detailed guide: [Automated Versioning](https://universe.razorpay.com/docs/getting-started-with-automated-versioning).

> **Note**
>
> Make sure you follow the prerequisites mentioned in the guide above for changesets to work as intended.

## 📊 Monitoring

### 🐞 Error Monitoring

Errors are inevitable when it comes to web development and applications. Hence the need for error monitoring solutions because you can’t put out new software applications without detecting software errors first.

This project comes integrated with `ErrorService` that helps you detect and track bugs and errors in your application.

Learn more about it here in our detailed guide: [Error Monitoring](https://universe.razorpay.com/docs/error-monitoring).

### 📈 Performance Monitoring

Universe provides various capabilities to monitor the performance of your application. For synthetic performance testing, we use SpeedCurve that provides performance metrics _after you deploy_ your application.

Universe also comes with it's own synthetic performance tooling that provides performance metrics _before you deploy_ your application. It can be setup with DangerJS to track & enforce lighthouse performance scores for your project.

Learn more about these here in our detailed guide: [Performance Monitoring](https://universe.razorpay.com/docs/performance-monitoring).

## 📖 Learn More

You can learn more in the [universe-cli](https://universe.razorpay.com/) documentation.
