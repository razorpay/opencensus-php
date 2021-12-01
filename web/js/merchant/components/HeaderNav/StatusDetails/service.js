import moment from 'moment';

import { merchantFetch } from 'merchant/utils/ajax';
import { getTimeinTwelveHourFormat } from './utilities';
import {
  BANKS,
  VPA_HANDLES,
  PSPs,
  CARD_ISSUERS,
  CARD_NETWORKS,
  CARDS_PAYMENT_METHOD,
  UPI_PAYMENT_METHOD,
  NETBANKING_PAYMENT_METHOD,
  PAYMENT_METHOD_MAP,
} from './constants';

export const fetchOngoingDowntimes = () => {
  return merchantFetch({
    url: 'payments/downtimes/ongoing',
    method: 'get',
  })
    .then((response) => {
      let data = [];
      if (Array.isArray(response)) {
        data = response;
      } else {
        data = response.data;
      }
      const cardNetworksOperational = [...CARD_NETWORKS];
      const cardIssuersOperational = [...CARD_ISSUERS];
      const vpaOperational = [...VPA_HANDLES];
      const pspOperational = [...PSPs];
      const netBankingOperational = [...BANKS];

      if (data.length === 0) {
        // Setting time
        const now = new Date();
        const time = getTimeinTwelveHourFormat(now);
        return new Promise((res) => {
          res({
            overallStatus: 'operational',
            cardDowntimes: {},
            upiDowntimes: {},
            netBankingDowntimes: {},
            cardNetworksOperational,
            cardIssuersOperational,
            vpaOperational,
            pspOperational,
            netBankingOperational,
            time,
            timeObj: now,
          });
        });
      } else {
        const cardDowntimes = {};
        const upiDowntimes = {};
        const netBankingDowntimes = {};
        let overallStatus = '';
        const methodsDown = [];

        data.forEach((downtime) => {
          switch (downtime.method) {
            case 'card':
              {
                const instrument = Object.keys(downtime.instrument)[0];
                if (instrument === 'network') {
                  if (!('network' in cardDowntimes)) {
                    cardDowntimes.network = {};
                  }

                  // Mapping to card network name
                  downtime.mapToName = true;

                  const network = CARD_NETWORKS.find(
                    (element) => element.code === downtime?.instrument?.network,
                  );
                  if (network != undefined) {
                    const networkName = network.networkName;
                    downtime.providerName = networkName;
                  }

                  const index = cardNetworksOperational.findIndex(
                    (element) => element.code === downtime?.instrument?.network,
                  );
                  if (index > -1) {
                    cardNetworksOperational.splice(index, 1);
                  }

                  switch (downtime.severity) {
                    case 'low':
                      if ('low' in cardDowntimes.network) cardDowntimes.network.low.push(downtime);
                      else cardDowntimes.network.low = [downtime];
                      break;
                    case 'medium':
                      if ('medium' in cardDowntimes.network)
                        cardDowntimes.network.medium.push(downtime);
                      else cardDowntimes.network.medium = [downtime];
                      break;
                    case 'high':
                      if ('high' in cardDowntimes.network)
                        cardDowntimes.network.high.push(downtime);
                      else cardDowntimes.network.high = [downtime];
                      break;
                    default:
                  }
                } else if (instrument === 'issuer') {
                  if (!('issuer' in cardDowntimes)) {
                    cardDowntimes.issuer = {};
                  }
                  // Mapping to card issuer name
                  downtime.mapToName = true;
                  const issuer = CARD_ISSUERS.find(
                    (element) => element.code === downtime?.instrument?.issuer,
                  );
                  if (issuer != undefined) {
                    const issuerName = issuer.issuerName;
                    downtime.providerName = issuerName;
                  }

                  const index = cardIssuersOperational.findIndex(
                    (element) => element.code === downtime?.instrument?.issuer,
                  );
                  if (index > -1) {
                    cardIssuersOperational.splice(index, 1);
                  }

                  switch (downtime.severity) {
                    case 'low':
                      if ('low' in cardDowntimes.issuer) cardDowntimes.issuer.low.push(downtime);
                      else cardDowntimes.issuer.low = [downtime];
                      break;
                    case 'medium':
                      if ('medium' in cardDowntimes.issuer)
                        cardDowntimes.issuer.medium.push(downtime);
                      else cardDowntimes.issuer.medium = [downtime];
                      break;
                    case 'high':
                      if ('high' in cardDowntimes.issuer) cardDowntimes.issuer.high.push(downtime);
                      else cardDowntimes.issuer.high = [downtime];
                      break;
                    default:
                  }
                }
              }

              break;
            case 'upi':
              {
                const instruments = Object.keys(downtime.instrument);
                let instrument;
                if (instruments.length > 0) instrument = instruments[0];
                else overallStatus = 'severeDrop';

                if (instrument === 'vpa_handle') {
                  if (!('vpa_handle' in upiDowntimes)) {
                    upiDowntimes.vpa_handle = {};
                  }
                  switch (downtime.severity) {
                    case 'low':
                      if ('low' in upiDowntimes.vpa_handle)
                        upiDowntimes.vpa_handle.low.push(downtime);
                      else upiDowntimes.vpa_handle.low = [downtime];
                      break;
                    case 'medium':
                      if ('medium' in upiDowntimes.vpa_handle)
                        upiDowntimes.vpa_handle.medium.push(downtime);
                      else upiDowntimes.vpa_handle.medium = [downtime];
                      break;
                    case 'high':
                      if ('high' in upiDowntimes.vpa_handle)
                        upiDowntimes.vpa_handle.high.push(downtime);
                      else upiDowntimes.vpa_handle.high = [downtime];
                      break;
                    default:
                  }
                  const index = vpaOperational.indexOf(downtime.instrument[instrument]);
                  if (index > -1) {
                    vpaOperational.splice(index, 1);
                  }
                } else if (instrument === 'psp') {
                  if (!('psp' in cardDowntimes)) {
                    upiDowntimes.psp = {};
                  }
                  // Mapping to psp name
                  downtime.mapToName = true;
                  const psp = PSPs.find((element) => element.code === downtime?.instrument?.psp);
                  if (psp != undefined) {
                    const pspName = psp.pspName;
                    downtime.providerName = pspName;
                  }

                  const index = pspOperational.findIndex(
                    (element) => element.code === downtime?.instrument?.psp,
                  );
                  if (index > -1) {
                    pspOperational.splice(index, 1);
                  }

                  switch (downtime.severity) {
                    case 'low':
                      if ('low' in upiDowntimes.psp) upiDowntimes.psp.low.push(downtime);
                      else upiDowntimes.psp.low = [downtime];
                      break;
                    case 'medium':
                      if ('medium' in upiDowntimes.psp) upiDowntimes.psp.medium.push(downtime);
                      else upiDowntimes.psp.medium = [downtime];
                      break;
                    case 'high':
                      if ('high' in upiDowntimes.psp) upiDowntimes.psp.high.push(downtime);
                      else upiDowntimes.psp.high = [downtime];
                      break;
                    default:
                  }
                }
              }
              break;
            case 'netbanking':
              {
                // Mapping to bank name
                downtime.mapToName = true;
                const bank = BANKS.find((element) => element.code === downtime?.instrument?.bank);
                if (bank != undefined) {
                  const bankName = bank.bankName;
                  downtime.providerName = bankName;
                }

                const index = netBankingOperational.findIndex(
                  (element) => element.code === downtime?.instrument?.bank,
                );
                if (index > -1) {
                  netBankingOperational.splice(index, 1);
                }

                switch (downtime.severity) {
                  case 'low':
                    if ('low' in netBankingDowntimes) netBankingDowntimes.low.push(downtime);
                    else netBankingDowntimes.low = [downtime];
                    break;
                  case 'medium':
                    if ('medium' in netBankingDowntimes) netBankingDowntimes.medium.push(downtime);
                    else netBankingDowntimes.medium = [downtime];
                    break;
                  case 'high':
                    if ('high' in netBankingDowntimes) netBankingDowntimes.high.push(downtime);
                    else netBankingDowntimes.high = [downtime];
                    break;
                  default:
                }
              }
              break;
            default:
          }
        });

        // Checking for the overall status
        if (overallStatus === 'severeDrop') {
          console.log('Severe drop');
        } else if (
          cardDowntimes?.network?.high?.length > 1 ||
          cardDowntimes?.issuer?.high?.length > 2 ||
          upiDowntimes?.vpa_handle?.high?.length > 2 ||
          upiDowntimes?.psp?.high?.length > 1 ||
          netBankingDowntimes.high > 2
        )
          overallStatus = 'majorDrops';
        else overallStatus = 'fewDrops';

        if (Object.keys(cardDowntimes).length > 0) {
          methodsDown.push('Cards');
        }
        if (Object.keys(upiDowntimes).length > 0) {
          methodsDown.push('UPI');
        }
        if (Object.keys(netBankingDowntimes).length > 0) {
          methodsDown.push('Net Banking');
        }

        // Setting time
        const now = new Date();
        const time = getTimeinTwelveHourFormat(now);

        return new Promise((res) => {
          res({
            overallStatus,
            methodsDown,
            cardDowntimes,
            upiDowntimes,
            netBankingDowntimes,
            cardNetworksOperational,
            cardIssuersOperational,
            vpaOperational,
            pspOperational,
            netBankingOperational,
            time,
            timeObj: now,
          });
        });
      }
    })
    .catch((err) => {
      return new Promise((res, rej) => {
        rej(err);
      });
    });
};

export const fetchScheduledDowntimes = () => {
  return merchantFetch({
    url: 'payments/downtimes/scheduled',
    method: 'get',
  })
    .then((response) => {
      let data = [];
      if (Array.isArray(response)) {
        data = response;
      } else {
        data = response.data;
      }
      const scheduledDowntimes = {};
      data.forEach((scheduledDowntime) => {
        const method = scheduledDowntime.method;
        switch (method) {
          case 'card':
            {
              if (!('card' in scheduledDowntimes)) {
                scheduledDowntimes.card = [];
              }
              const instrument = Object.keys(scheduledDowntime.instrument)[0];
              if (instrument === 'issuer') {
                // Mapping to card issuer name
                scheduledDowntime.mapToName = true;
                const issuer = CARD_ISSUERS.find(
                  (element) => element.code === scheduledDowntime?.instrument?.issuer,
                );
                if (issuer != undefined) {
                  const issuerName = issuer.issuerName;
                  scheduledDowntime.providerName = issuerName;
                }
              }
              scheduledDowntimes.card.push(scheduledDowntime);
            }
            break;
          case 'upi':
            {
              if (!('upi' in scheduledDowntimes)) {
                scheduledDowntimes.upi = [];
              }
              const instrument = Object.keys(scheduledDowntime.instrument)[0];
              if (instrument === 'psp') {
                // Mapping to psp name
                scheduledDowntime.mapToName = true;
                const psp = PSPs.find(
                  (element) => element.code === scheduledDowntime?.instrument?.psp,
                );
                if (psp != undefined) {
                  const pspName = psp.pspName;
                  scheduledDowntime.providerName = pspName;
                }
              }
              scheduledDowntimes.upi.push(scheduledDowntime);
            }
            break;
          case 'netbanking':
            {
              if (!('netbanking' in scheduledDowntimes)) {
                scheduledDowntimes.netbanking = [];
              }

              // Mapping to bank name
              scheduledDowntime.mapToName = true;
              const bank = BANKS.find(
                (element) => element.code === scheduledDowntime?.instrument?.bank,
              );
              if (bank != undefined) {
                const bankName = bank.bankName;
                scheduledDowntime.providerName = bankName;
              }
              scheduledDowntimes.netbanking.push(scheduledDowntime);
            }
            break;

          default:
            break;
        }
      });
      return new Promise((res) => {
        res(scheduledDowntimes);
      });
    })
    .catch((err) => {
      return new Promise((res, rej) => {
        rej(err);
      });
    });
};

export const fetchHistoricalDowntimes = async (skip, count, paymentMethod) => {
  try {
    const startDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
    const endDate = moment().format('YYYY-MM-DD');
    const method = PAYMENT_METHOD_MAP[paymentMethod];
    let data = [];
    const response = await merchantFetch({
      url: 'payments/downtimes/resolved',
      method: 'get',
      data: {
        method,
        skip,
        count,
        startDate,
        endDate,
      },
    });
    if (Array.isArray(response)) {
      data = response;
    } else {
      data = response.data;
    }
    data.forEach((historicalDowntime) => {
      let instrument = '';
      for (const key in historicalDowntime.instrument) {
        if (historicalDowntime.instrument.hasOwnProperty(key)) {
          instrument = key;

          switch (paymentMethod) {
            case CARDS_PAYMENT_METHOD:
              if (instrument === 'issuer') {
                historicalDowntime.mapToName = true;
                const issuer = CARD_ISSUERS.find(
                  (element) => element.code === historicalDowntime?.instrument?.issuer,
                );
                historicalDowntime.providerName = issuer.issuerName;
              }

              if (instrument === 'network') {
                historicalDowntime.mapToName = true;
                const network = CARD_NETWORKS.find(
                  (element) => element.code === historicalDowntime?.instrument?.network,
                );

                historicalDowntime.providerName = network.networkName;
              }
              break;
            case UPI_PAYMENT_METHOD:
              if (instrument === 'psp') {
                historicalDowntime.mapToName = true;
                const psp = PSPs.find(
                  (element) => element.code === historicalDowntime?.instrument?.psp,
                );
                historicalDowntime.providerName = psp.pspName;
              }
              break;
            case NETBANKING_PAYMENT_METHOD:
              {
                historicalDowntime.mapToName = true;
                const bank = BANKS.find(
                  (element) => element.code === historicalDowntime?.instrument?.bank,
                );
                historicalDowntime.providerName = bank.bankName;
              }
              break;
            default:
              break;
          }
        }
      }
    });
    return data;
  } catch (err) {
    throw new Error(err);
  }
};
