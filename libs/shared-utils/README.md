# SharedUtils

This library contains shared utility functions that are used across different microapps and dashboards within the project. The goal of this library is to provide reusable utility functions while keeping them organized based on their scope and dashboard-specific needs.

## Folder Structure

- **src/**
  - **common/**: Contains utility functions that are available globally to all microapps within the dashboard.
  - **scoped/**: Contains utilities that are segregated based on the dashboards in our application (e.g., Merchant, MerchantLA, Pokedex, X). Each dashboard has its own set of utilities.

## Usage

- This library is imported into the project using:
  ```typescript
  import { utilFunction } from '@libs/shared-utils';
 
