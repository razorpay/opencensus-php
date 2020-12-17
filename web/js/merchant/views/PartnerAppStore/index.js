/**
 *
 * ASSETS: /web/css/assets/app-store/
 * CSS: /web/css/ui/merchant/PartnerAppStore.styl
 * DATA: ./data/
 *
 * On Routes-
 * /app-store/          --> ./index.js (this file which eventually exports ./PartnerAppStore.js)
 * /app-store/:partner  --> ./PartnerPage.js
 *
 * How to add data-
 * 1. Add entry to ./data/index.js
 * 2. The content has to be in a specific way to match the layout.
 * 3. Check out ./data/content/zoho.js for sample layout.
 *
 */

export { default } from './PartnerAppStore.js';
