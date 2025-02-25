


/**
 * Represents cardless EMI providers available for the merchant.
 */
export type RazorpayUserMerchantCardlessEmiProviders = {
    /** Boolean flag for Walnut 369 cardless EMI provider */
    walnut369: number;
  
    /** Boolean flag for ZestMoney cardless EMI provider */
    zestmoney: number;
  
    /** Boolean flag for EarlySalary cardless EMI provider */
    earlysalary: number;
  
    /** Boolean flag for HDFC cardless EMI provider */
    hdfc: number;
  
    /** Boolean flag for ICICI cardless EMI provider */
    icic: number;
  
    /** Boolean flag for Bank of Baroda cardless EMI provider */
    barb: number;
  
    /** Boolean flag for Kotak Mahindra Bank (KKBK) cardless EMI provider */
    kkbk: number;
  
    /** Boolean flag for Federal Bank cardless EMI provider */
    fdrl: number;
  
    /** Boolean flag for IDFC First Bank (IDFB) cardless EMI provider */
    idfb: number;
  
    /** Boolean flag for Home Credit India (HCIN) cardless EMI provider */
    hcin: number;
  
    /** Boolean flag for KrazyBee (KRBE) cardless EMI provider */
    krbe: number;
  
    /** Boolean flag for CashE (CSHE) cardless EMI provider */
    cshe: number;
  
    /** Boolean flag for TVS Credit Services cardless EMI provider */
    tvsc: number;
  
    /** Boolean flag for Liquiloans cardless EMI provider */
    liquiloans: number;
  
    /** Boolean flag for instant EMI provider */
    instant_emi: number;
  
    /** Other providers, if available */
    [key: string]: number;
  };