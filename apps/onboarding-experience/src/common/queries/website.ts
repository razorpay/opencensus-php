import { gql } from 'graphql-tag';
/**
 * GraphQL mutation to add a selected plugin for a merchant.
 * This mutation allows the user to associate a specific plugin with a merchant's website.
 */

export const ADD_MERCHANT_PLUGIN_MUTATION = gql`
  mutation addMerchantSelectedPlugin($website: String!, $pluginName: String!) {
    addMerchantSelectedPlugin(website: $website, pluginName: $pluginName) {
      plugins {
        website
        selectedPlugin
      }
    }
  }
`;
