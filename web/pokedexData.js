import { groupBy } from 'rzp/utils/pokedex';
import { titleCase } from 'rzp/utils/rzp-utils';

import {
 tabsOrder, tabsMeta
} from 'merchant/containers/Home/KeyMetrics/data';
import {
  getBankName,
} from 'merchant/containers/Home/PaymentMethods/data'; 

import {
  globalGroupTitleMap,
  getDefaultFilter,
} from 'rzp/utils/pokedex';

const RETRIES = "retries",
      PAYMENT_METHODS = "paymentMethods",
      SOURCES = "sources",
      SUCCESS_RATE = 'successRate';

let paymentMethodFilterVals = [
  {
    text: "All Payment Methods",
    value: "all"
  }
], sourceFilterVals = [
  {
    text: "All Sources",
    value: "all"
  }
];

const paymentMethodsFilter = {
  name: PAYMENT_METHODS,
  values: paymentMethodFilterVals,
  disabled: true
}, sourcesFilter = {
  name: SOURCES,
  values: sourceFilterVals,
  disabled: true
};

// map of payment method to sources available
// for the payment method
const paymentMethodSourcesMap = {};

const successRateMeta = {
  name: SUCCESS_RATE,
  title: 'Success Rate',
  grouping: [],
  isPercent: true,
  filters: [{
    name: RETRIES,
    values: [
      {
        text: "Without Retries",
        value: false
      }, {
        text: "With Retries",
        value: true
      }
    ],
    disabled: false
  }, paymentMethodsFilter, sourcesFilter],
  index: 'payments',
  groupByColumnName: "Success Rate",
  noGrouping: true,
  valueKey: 'success_rate',
  getCountQuery: function (filterBy) {

    let aggType = this.valueKey;

    if (filterBy) {

      Object.keys(filterBy).forEach((filterName) => {
      
        const filterVal = filterBy[filterName];

        // if retries need to be included, change the index name
        if (filterName === RETRIES && filterVal) {

          aggType += "_with_retry";
        }
      });
    }

    return {
      [this.name]: {
        agg_type: aggType,
        filter_key: this.name,
        details: {
          index: this.index
        }
      }
    }
  },
  getHistogramQuery: function ({groupBy, breakdown, filterBy={}}) {

    let aggType = this.valueKey;

    const aggDetails = {
      index: this.index,
      group_by: [`histogram_${breakdown}`]
    };

    Object.keys(filterBy).forEach((filterName) => {
    
      const filterVal = filterBy[filterName];

      // if retries need to be included, change the index name
      if (filterName === RETRIES && filterVal) {

        aggType += "_with_retry";
      }
    });

    return {
      [`${this.name}Histogram`]: {
        agg_type: aggType,
        filter_key: this.name,
        details: {
          index: this.index,
          group_by: [`histogram_${breakdown}`]
        }
      }
    };
  },
  getFilterQuery(startTime, endTime, filterBy) {
 
    let filter = getDefaultFilter(startTime, endTime);

    if (!filterBy) {
    
      return {[this.name]: [filter]};
    }

    let paymentMethod = filterBy[PAYMENT_METHODS],
        sources = filterBy[SOURCES],
        hasSources = sources !== "all";

    if (paymentMethod !== "all") {
   
      if (paymentMethod.indexOf("card") === 0)  {
      
        const paymentMethodArr = paymentMethod.split("-"),
              cardType = paymentMethodArr[1];

        paymentMethod = paymentMethodArr[0];

        if (cardType) {
        
          filter.type = [cardType];
        }

        if (hasSources) {
        
          const sourcesArr = sources.split("-"),
                sourceType = sourcesArr[1],
                sourceValue = sourcesArr[0];

          if (sourceType === "network" || sourceType === "bank") {
          
            filter[sourceType] = [sourceValue];
          }
        }
      } else if (paymentMethod === "netbanking" && hasSources) {
      
        filter.bank = [sources];
      } else if (paymentMethod === "wallet" && hasSources) {
      
        filter.wallet = [sources];
      }

      filter.method = [paymentMethod];
    }

    return {[this.name]: [filter]};
  }
};

tabsOrder.push(SUCCESS_RATE);
tabsMeta[SUCCESS_RATE] = successRateMeta;

const populatePaymentMethods = (result) => {

  const groupedData = groupBy(result, "method"),
        paymentMethods = [],
        groups = Object.keys(groupedData);

  if (groups.length === 0) {
  
    return;
  }
   
  paymentMethodSourcesMap.all = [];

  Object.keys(groupedData).forEach((method) => {
    
    const values = groupedData[method];

    if (method === "card") {
   
      const cardData = {banks: {}, networks: {}},
            cardsDataMap = {};

      cardsDataMap.all = {
        ...cardData
      };

      paymentMethodFilterVals.push({
        "text": "All Cards",
        "value": method,
        "type": "all"
      });

      values.forEach((value) => {
      
        const {type, network, issuer} = value;

        let cardDataMap = cardsDataMap[type];

        if (!cardDataMap) {

          cardDataMap = cardsDataMap[type] = {
            ...cardData
          };

          paymentMethodFilterVals.push({
            "text": `${titleCase(type)} Card`,
            "value": `${method}-${type}`,
            "type": type
          });
        }

        if (!cardsDataMap.all.networks[network]) {
        
          cardsDataMap.all.networks[network] = "";
        }

        if (!cardsDataMap.all.banks[issuer]) {
        
          cardsDataMap.all.banks[issuer] = "";
        }

        if (!cardDataMap.networks[network]) {
        
          cardDataMap.networks[network] = "";
        }

        if (!cardDataMap.banks[issuer]) {
        
          cardDataMap.banks[issuer] = "";
        }
      });

      Object.keys(cardsDataMap).forEach(cardType => {
      
        const cardDataMap = cardsDataMap[cardType],
              options = cardDataMap.options = [];

        options.push({
          label: "Networks",
          options: Object.keys(cardDataMap.networks)
                         .sort()
                         .map(network => {
                           return {
                             text: (
                                     network
                                       ? titleCase(network)
                                       : "Unknown"
                                   ) + " Network",
                             value: network + "-network"
                           };
                         })
        });

        options.push({
          label: "Banks",
          options: Object.keys(cardDataMap.banks)
                         .sort()
                         .map(bank => {
                           return {
                             text: getBankName(bank),
                             value: bank + "-bank"
                           };
                         })
        });

        cardsDataMap[cardType] = options;
      });

      paymentMethodSourcesMap[method] = cardsDataMap;
    } else {
        
      if (method === "netbanking") {
    
        const availableBanks = {};

        values.forEach(value => {
        
          const {bank} = value;

          return !availableBanks[bank] && 
                 (availableBanks[bank] = "");
        });

        paymentMethodSourcesMap[method] = Object.keys(availableBanks)
                                                .sort()
                                                .map((bank) => {
                                                  return {
                                                    text: getBankName(bank),
                                                    value: bank
                                                  };
                                                });
      } else if (method === "wallet") {
      
        const availableWallets = {};

        values.forEach(value => {
        
          const {wallet} = value;

          return !availableWallets[wallet] && 
                 (availableWallets[wallet] = "");
        });

        paymentMethodSourcesMap[method] = Object.keys(availableWallets)
                                                .sort()
                                                .map(wallet => {
                                                  return {
                                                    text: titleCase(wallet),
                                                    value: wallet
                                                  };
                                                });
      } else {
      
        paymentMethodSourcesMap[method] = [];
      }

      paymentMethodFilterVals.push({
        "text": globalGroupTitleMap[method] || titleCase(method),
        "value": method
      });
    }
  });

  paymentMethodsFilter.disabled = false;
};

const populateSourceFilters = (selectedPaymentMethodOption) => {

  const {value, type} = selectedPaymentMethodOption;

  let sourceFilters = [];

  if (value.indexOf("card") === 0) {
  
    sourceFilters = paymentMethodSourcesMap.card[type];
  } else {
  
    sourceFilters = paymentMethodSourcesMap[value];
  }

  sourcesFilter.values = sourceFilterVals = [
    sourceFilterVals[0],
    ...sourceFilters
  ];

  sourcesFilter.disabled = sourceFilters.length === 0;
};

export {
  populatePaymentMethods,
  populateSourceFilters,
  SUCCESS_RATE,
  PAYMENT_METHODS,
  successRateMeta,
  paymentMethodFilterVals,
  sourceFilterVals
};
