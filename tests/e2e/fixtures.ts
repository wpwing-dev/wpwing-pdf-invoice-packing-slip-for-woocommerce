import { test as base, expect } from '@playwright/test';

// Authenticated as admin via the chromium project's storageState.
// Extend with page objects / helpers here as later phases need them.
export const test = base;
export { expect };
