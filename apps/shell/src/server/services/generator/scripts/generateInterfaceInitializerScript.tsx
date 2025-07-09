import React from 'react';

export const generateInterfaceInitializerScript = () => {
  return (
    <script
      key="hot-jar"
      type="module"
      dangerouslySetInnerHTML={{
        __html: `
        var noop = function() {};

        //Empty Interface for rzpQ
        window.rzpQ = {
            component: noop,            // Track components
            initiated: noop,            // User starts an activity
            dropped: noop,              // User drops an activity
            clicked: noop,              // User click activity
            viewed: noop,               // User view activity
            success: noop,              // Successfully completes activity
            failed: noop,               // A failure occured
            push: noop,                 // Explicitly push as custom event to the queue
            drain: noop,                // Clears event queue
            setUser: noop,              // Set a user one time
            interaction: noop,
            defineEventModifiers: noop, //Extends to set custom event properties
    
            //Any modifiers
            onbr: function() {
                return window.rzpQ;
            },
            merchantActions: function() {
                return window.rzpQ;
            },
            xMerchantActions: function() {
                return window.rzpQ;
            },
            productOnboarding: function() {
                return window.rzpQ;
            },
            routeActions: function() {
                return window.rzpQ;
            },
            paymentPages: function() {
                return window.rzpQ;
            },
            paymentPage: function() {
                return window.rzpQ;
            },
            paymentStores: function() {
                return window.rzpQ;
            },
            paymentLinks: function() {
                return window.rzpQ;
            },
            reporting: function() {
                return window.rzpQ;
            },
            chargeAtWill: function() {
                return window.rzpQ;
            },
            invoice: function() {
                return window.rzpQ;
            },
            now: function() {
                return window.rzpQ;
            },
            paymentButtons: function() {
                return window.rzpQ;
            },
            subscriptionButtons: function() {
                return window.rzpQ;
            },
            smartCollect: function() {
                return window.rzpQ;
            },
            subscription: function() {
                return window.rzpQ;
            },
            qrCode: function() {
                return window.rzpQ;
            }
        };`,
      }}
    />
  );
};
