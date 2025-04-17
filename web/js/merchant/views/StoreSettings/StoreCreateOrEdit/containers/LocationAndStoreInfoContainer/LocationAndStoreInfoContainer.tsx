import React, { useEffect, useState } from 'react';
import {
  Heading,
  Card,
  CardBody,
  Box,
  Divider,
  Dropdown,
  AutoComplete,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Spinner,
} from '@razorpay/blade/components';
import { getCities, getStates, getAllCountries } from '@razorpay/i18nify-js';
import { useFormikContext } from 'formik';
import { useSearchParams } from 'react-router-dom';

import StoreContactDetails from 'merchant/views/StoreSettings/StoreCreateOrEdit/containers/StoreContactDetails';
import { useStoresCreateStore } from 'merchant/views/StoreSettings/StoreCreateOrEdit/stores/storesCreateFormStore';
import {
  I18nifyCountryCodeType,
  StoreCreateFormValues,
} from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';
import FormikTextArea from 'merchant/views/StoreSettings/common/components/FormFields/FormikTextArea';
import FormikTextInputField from 'merchant/views/StoreSettings/common/components/FormFields/FormikTextInputField';
import { GeoLocationOptions, GeoLocation, LocationAndStoreInfoContainerProps } from './types';

function LocationAndStoreInfoContainer({ isLoading }: LocationAndStoreInfoContainerProps) {
  const [fetchedGeo, setFetchedGeo] = useState<GeoLocationOptions>({
    countries: [],
    states: [],
    cities: [],
  });
  const [selectedGeo, setSelectedGeo] = useState<GeoLocation>({
    country: '',
    state: '',
    city: '',
  });
  const [filteredGeo, setFilteredGeo] = useState<GeoLocationOptions>({
    countries: [],
    states: [],
    cities: [],
  });
  const {
    values: formValues,
    errors,
    touched,
    setFieldValue,
  } = useFormikContext<StoreCreateFormValues>();

  const { basicInfoForm, neglectStateAndCityCheck, setNeglectStateAndCityCheck } =
    useStoresCreateStore();
  
  const [searchParams] = useSearchParams();
  const storeId = searchParams.get('id');

  useEffect(() => {
    if (basicInfoForm.country && storeId) {
      getAllCountries().then((data) => {
        const countries = Object.keys(data).map((key) => ({
          label: data[key].country_name,
          value: key as I18nifyCountryCodeType,
        }));
        const country = countries.find((_country) => _country.label === basicInfoForm.country);
        setSelectedGeo((state) => ({
          ...state,
          country: country?.label,
        }));
        if (country?.value)
          getStates(country?.value)
            .then((stateData) => {
              const stateList = Object.keys(stateData).map((key) => ({
                label: stateData[key].name,
                value: key,
              }));
              const selectedState = stateList.find((state) => state.label === basicInfoForm.state);
              setSelectedGeo((state) => ({
                ...state,
                country: country?.label,
                state: basicInfoForm.state,
              }));
              getCities(country?.value, selectedState?.value).then((cityData) => {
                const cityList = Object.keys(cityData).map((key) => ({
                  label: cityData[key].name,
                  value: key,
                }));
                setFetchedGeo({
                  countries,
                  states: stateList,
                  cities: cityList,
                });

                setFilteredGeo({
                  countries: countries.slice(0, 10),
                  states: stateList.slice(0, 10),
                  cities: cityList.slice(0, 10),
                });

                setSelectedGeo((state) => ({
                  ...state,
                  country: country?.label,
                  state: basicInfoForm.state,
                  city: basicInfoForm.city,
                }));
              });
            })
            .catch(() => {
              setNeglectStateAndCityCheck(true);
              setFetchedGeo({
                ...fetchedGeo,
                states: [],
                cities: [],
              });
              setFilteredGeo({
                countries: [],
                states: [],
                cities: [],
              });
              setSelectedGeo({
                country: '',
                state: '',
                city: '',
              });
            });
      });
    } else {
      getAllCountries().then((data) => {
        const countries = Object.keys(data).map((key) => ({
          label: data[key].country_name,
          value: key as I18nifyCountryCodeType,
        }));
        setFetchedGeo({
          countries,
          states: [],
          cities: [],
        });
        setFilteredGeo({
          countries: countries.slice(0, 10),
          states: [],
          cities: [],
        });
        setSelectedGeo({
          country: '',
          state: '',
          city: '',
        });
      });
    }
  }, [basicInfoForm, storeId]);

  const isLocationFieldsMandatory = formValues.storeType === 'OFFLINE';

  const getLocationFieldsNecessityIndicator = () => {
    return formValues.storeType === 'OFFLINE' ? 'required' : 'none';
  };

  const onCountryChange = async ({ values }) => {
    const country = fetchedGeo.countries.find((country) => country.value === values[0]);
    setFieldValue('country', country?.label);
    setFieldValue('state', '');
    setFieldValue('city', '');
    setSelectedGeo({
      country: country?.label || '',
      state: '',
      city: '',
    });
    try {
      const stateData = await getStates(values[0] as I18nifyCountryCodeType);
      if (neglectStateAndCityCheck) {
        setNeglectStateAndCityCheck(false);
      }
      const stateList = Object.keys(stateData).map((key) => ({
        label: stateData[key].name,
        value: key,
      }));
      setFetchedGeo({
        ...fetchedGeo,
        states: stateList,
        cities: [],
      });
      setFilteredGeo({
        countries: fetchedGeo.countries.slice(0, 10),
        states: stateList.slice(0, 10),
        cities: [],
      });
    } catch (e) {
      setNeglectStateAndCityCheck(true);
      setFilteredGeo({
        ...fetchedGeo,
        countries: fetchedGeo.countries.slice(0, 10),
        states: [],
        cities: [],
      });
      setFetchedGeo({
        ...fetchedGeo,
        states: [],
        cities: [],
      });
    }
  };

  const onStateChange = async ({ values }) => {
    const country = fetchedGeo.countries.find((country) => country.label === selectedGeo.country);
    const states = fetchedGeo.states;
    const state = fetchedGeo?.states?.find((state) => state.value === values[0]);

    setFieldValue('state', state?.label);
    setFieldValue('city', '');
    setSelectedGeo({
      ...selectedGeo,
      state: state?.label || '',
      city: '',
    });
    try {
      if (country?.value) {
        const cityData = await getCities(country?.value, values[0]);
        if (neglectStateAndCityCheck) {
          setNeglectStateAndCityCheck(false);
        }
        const cityList = Object.keys(cityData).map((key) => ({
          label: cityData[key].name,
          value: key,
        }));
        setFilteredGeo({
          ...filteredGeo,
          states: states?.slice(0, 10),
          cities: cityList.slice(0, 10),
        });
        setFetchedGeo({
          ...fetchedGeo,
          states,
          cities: cityList,
        });
      }
    } catch (e) {
      setNeglectStateAndCityCheck(true);
      setFetchedGeo({
        ...fetchedGeo,
        states,
        cities: [],
      });
      setFilteredGeo({
        ...fetchedGeo,
        states: states?.slice(0, 10),
        cities: [],
      });
    }
  };

  const onCityChange = ({ values }) => {
    const cities = fetchedGeo.cities;
    const city = cities.find((city) => city.value === values[0]);
    setFieldValue('city', city?.label);
    setFetchedGeo({
      ...fetchedGeo,
      cities,
    });
    setSelectedGeo({
      ...selectedGeo,
      city: city?.label || '',
    });
    setFilteredGeo({
      ...filteredGeo,
      cities: cities.slice(0, 10),
    });
  };
  return (
    <Card data-analytics-name="store-location-and-contact-section">
      <CardBody>
        <Heading>Location & Store Contact Details</Heading>
        {isLoading ? (
          <Box display="flex" alignItems="center" justifyContent="center" height="100%">
            <Spinner accessibilityLabel="Location & Store Contact loading" />
          </Box>
        ) : (
          <Box marginTop="spacing.8" display="flex" gap="spacing.4" flexDirection="column">
            <Box width="70%" display="flex" gap="spacing.6" flexDirection="column">
              <FormikTextInputField
                name="displayAddress"
                label="Display Address"
                placeholder="Enter Display Address"
                isRequired={isLocationFieldsMandatory}
                necessityIndicator={getLocationFieldsNecessityIndicator()}
                helpText="This address will be shown as a short address in your digital bills."
                labelPosition="left"
              />
              <FormikTextArea
                name="address"
                label="Address"
                placeholder="Enter Store Address"
                necessityIndicator={getLocationFieldsNecessityIndicator()}
                isRequired={isLocationFieldsMandatory}
                labelPosition="left"
              />
              <Dropdown>
                <AutoComplete
                  necessityIndicator={getLocationFieldsNecessityIndicator()}
                  isRequired={isLocationFieldsMandatory}
                  inputValue={selectedGeo.country}
                  onChange={onCountryChange}
                  onInputValueChange={({ value }) => {
                    if (value?.length === 0) {
                      setFieldValue('country', '');
                      setFieldValue('state', '');
                      setFieldValue('city', '');
                    }
                    const filteredCountryValues = fetchedGeo.countries.filter((country) =>
                      country.label.toLowerCase().includes(value?.toLowerCase() || ''),
                    );
                    setFilteredGeo({
                      ...filteredGeo,
                      countries: filteredCountryValues.slice(0, 10),
                    });
                    setSelectedGeo({
                      country: value || '',
                      state: '',
                      city: '',
                    });
                  }}
                  label="Country"
                  labelPosition="left"
                  placeholder="Select Country"
                  filteredValues={filteredGeo.countries.map((country) => country.value)}
                  validationState={touched.country && errors.country ? 'error' : 'none'}
                  errorText={errors.country || ''}
                  helpText="Please search and select the desired country"
                />
                <DropdownOverlay>
                  <ActionList>
                    {filteredGeo.countries.map((country) => (
                      <ActionListItem
                        title={country.label}
                        value={country.value}
                        key={country.value}
                      />
                    ))}
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>
              {formValues.country && fetchedGeo?.states?.length ? (
                <Dropdown>
                  <AutoComplete
                    necessityIndicator={getLocationFieldsNecessityIndicator()}
                    isRequired={isLocationFieldsMandatory}
                    inputValue={selectedGeo.state}
                    onChange={onStateChange}
                    onInputValueChange={({ value }) => {
                      if (value?.length === 0) {
                        setFieldValue('state', '');
                        setFieldValue('city', '');
                      }
                      const states = fetchedGeo.states;
                      const filteredStateValues = states.filter((state) =>
                        state.label.toLowerCase().includes(value?.toLowerCase() || ''),
                      );
                      setFilteredGeo({
                        ...filteredGeo,
                        states: filteredStateValues.slice(0, 10),
                      });
                      setSelectedGeo({
                        ...selectedGeo,
                        state: value || '',
                      });
                    }}
                    label="State"
                    labelPosition="left"
                    placeholder="Select State"
                    filteredValues={filteredGeo.states.map((state) => state.value)}
                    validationState={touched.state && errors.state ? 'error' : 'none'}
                    errorText={errors.state || ''}
                    helpText="Please search and select the desired state"
                  />
                  <DropdownOverlay>
                    <ActionList>
                      {filteredGeo.states.map((state) => (
                        <ActionListItem title={state.label} value={state.value} key={state.value} />
                      ))}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
              ) : null}
              {formValues.state && fetchedGeo?.cities?.length ? (
                <Dropdown>
                  <AutoComplete
                    necessityIndicator={getLocationFieldsNecessityIndicator()}
                    isRequired={isLocationFieldsMandatory}
                    inputValue={selectedGeo.city}
                    onChange={onCityChange}
                    onInputValueChange={({ value }) => {
                      if (value?.length === 0) {
                        setFieldValue('city', '');
                      }
                      const cities = fetchedGeo?.cities || [];
                      const filteredCityValues = cities.filter((city) =>
                        city.label.toLowerCase().includes(value?.toLowerCase() || ''),
                      );
                      setFilteredGeo({
                        ...fetchedGeo,
                        cities: filteredCityValues.slice(0, 10),
                      });
                      setSelectedGeo({
                        ...selectedGeo,
                        city: value || '',
                      });
                    }}
                    label="City"
                    labelPosition="left"
                    placeholder="Select City"
                    filteredValues={filteredGeo.cities.map((state) => state.value)}
                    validationState={touched.city && errors.city ? 'error' : 'none'}
                    errorText={errors.city || ''}
                    helpText="Please search and select the desired city"
                  />
                  <DropdownOverlay>
                    <ActionList>
                      {filteredGeo.cities.map((city) => (
                        <ActionListItem title={city.label} value={city.value} key={city.value} />
                      ))}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
              ) : null}
              <FormikTextInputField
                name="pinCode"
                label="Pincode"
                placeholder="Enter Pincode"
                necessityIndicator={getLocationFieldsNecessityIndicator()}
                isRequired={isLocationFieldsMandatory}
                labelPosition="left"
              />
            </Box>
            <Divider dividerStyle="dashed" marginY="spacing.4" />
            <Box width="70%" display="flex" gap="spacing.6" flexDirection="column">
              <StoreContactDetails />
            </Box>
          </Box>
        )}
      </CardBody>
    </Card>
  );
}

export default LocationAndStoreInfoContainer;
