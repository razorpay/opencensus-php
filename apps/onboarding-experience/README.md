# Onboarding Experience Microapp

A microapp that manages the user onboarding flows and first-time user experiences (FTUX) for the Razorpay dashboard.

## Overview

The Onboarding Experience microapp handles the user onboarding journey and first-time user experiences within the Razorpay dashboard. It is designed as a modular component that can be integrated with the main dashboard application.

## Features

- First-time user experience (FTUX) flows
- Merchant onboarding processes
- Interactive onboarding components

## FTUX Module

The First Time User Experience (FTUX) module is a core component of this microapp that provides:

- **Personalized Onboarding**: A dynamically generated experience based on the merchant's profile and business needs
- **Component-based Architecture**: Modular UI sections that can be conditionally displayed based on the merchant's state
- **Interactive Elements**: Including:
  - Welcome header with personalized merchant information
  - Product recommendation sections
  - Step-by-step guides via accordion components
  - Payment method configuration options
  - Transaction banners and status displays
  - No-code payment solution sections
  - Website integration nudges

The FTUX experience adapts to the merchant's journey, showing different components as they progress through the onboarding flow.

## Technology Stack

- React 17
- TypeScript
- Razorpay Blade Component Library
- TanStack Query (React Query)
- Styled Components
- React Router v6

## Development

### Prerequisites

- Node.js (>= 22.15.0)
- npm (>= 8.1.2) or pnpm (>= 10.10.0)

### Setup

1. Clone the repository:
   ```
   git clone https://github.com/razorpay/dashboard.git
   ```

2. Follow guidelines in root Readme.md

### Project Structure

```
src/
├── app/                 # Core application components
│   └── FTUX/            # First-time user experience module
│       │                # Other modules can be added in similar way
│       ├── components/  # UI components for the FTUX experience
│       ├── constants/   # FTUX-specific constants
│       ├── context/     # React context providers for FTUX
│       ├── hooks/       # Custom React hooks for FTUX functionality
│       ├── modals/      # Modal components for the FTUX flow
│       ├── types/       # TypeScript interfaces and type definitions
│       └── utils/       # Helper functions for FTUX
├── assets/              # Static assets (images, icons, etc.)
├── bootstrap/           # Application initialization
├── common/              # Shared utilities and components used across modules
├── container/           # Container components that provide layout and structure
├── exposed/             # Exposed entry points for the host app
│   └── entry/           # Entry point modules that can be imported by other apps
├── services/            # API and service integrations
```

## App Architecture

The onboarding-experience microapp follows a modular architecture:

1. **Entry Points**: Exposed via the `exposed/entry` directory, allowing the host application to import specific modules
2. **Container Components**: Provide the overall layout and structure for the microapp
3. **Core Modules**: 
   - FTUX: Manages the first-time user experience
   - Future modules can be added in a similar pattern under the `app` directory
4. **Shared Resources**: Common components, utilities, and services that can be used across different modules
5. **Lazy Loading**: Components are loaded dynamically to improve performance

## Testing

- Unit tests: `pnpm test:jest`
- E2E tests: `pnpm test:e2e`

## Build

- Development build: `pnpm devstack:build`
- Canary build: `pnpm canary:build`
- Production build: `pnpm production:build`

## Integration

This microapp is designed to be loaded via the exposed entry points by the host application. For example, the entry point for FTUX is:

```typescript
// To import the FTUX module
import FTUX from '@federated/apps/onboarding-experience/entry/FTUX';
```