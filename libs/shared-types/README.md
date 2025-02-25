# SharedTypes

This library contains shared type definitions used across the dashboard project. The purpose of this library is to centralize the type definitions, making them accessible to all microapps and modules within the dashboard.

## Folder Structure

- **Types/**
  - **common/**: Contains types that are used globally across all microapps and applications, such as types related to user data (e.g., `RazorpayUser`).
  - **microapps/**: Contains types specific to individual microapps. Each microapp can reference types from other microapps as needed.

## Usage

- This library is imported into the project using:
  ```typescript
  import { TypeName } from '@libs/shared-types';
