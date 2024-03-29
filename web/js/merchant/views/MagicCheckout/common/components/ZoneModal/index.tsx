import React, { useEffect, useState, useRef, useMemo } from 'react';
import { SearchIcon, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import AutoSizer from 'react-virtualized/dist/commonjs/AutoSizer';
import CellMeasurer, { CellMeasurerCache } from 'react-virtualized/dist/commonjs/CellMeasurer';
import List from 'react-virtualized/dist/commonjs/List';
import { bindActionCreators } from 'redux';

import Spinner from 'common/ui/Spinner';
import { merchantFetch } from 'merchant/utils/ajax';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import SettingsModal from 'merchant/views/MagicCheckout/common/components/SettingsModal';
import { MODAL_MODES } from 'merchant/views/MagicCheckout/common/components/SettingsModal/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import ZoneItem from './Item';
import { DEPTH_MAP, GLOBAL_KEY, VISIBLE_STATES } from './constants';
import { ModalWrapper } from './styles';
import { CountryMap, Zone, ZoneModalProps } from './types';
import {
  buildCountriesData,
  forAllCountries,
  forAllStates,
  getLocationsPayload,
  updateTotalSelectedStatus,
} from './helpers';

const ZoneModal = ({
  isOpen,
  zoneType,
  zone,
  item_category_id,
  mode,
  countriesUrl,
  createZone,
  updateZone,
  closeModal,
  showNotification,
  loading,
  isZoneFetching,
}: ZoneModalProps) => {
  const selectedZone = zone;
  const isEditMode: boolean = mode === MODAL_MODES.EDIT;
  const MODAL_HEADER = `${isEditMode ? 'Edit' : 'Create'} ${zoneType} zones`;
  const [searchText, setSearchText] = useState<string>('');
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string>('');

  const [filteredCountries, setFilteredCountries] = useState<CountryMap[]>([]);
  const [collapsibleCountries, setCollapsibleCountries] = useState<Record<string, any>>({});
  const virtualizedCache = useRef(
    new CellMeasurerCache({
      fixedWidth: true,
      defaultHeight: 60,
    }),
  );
  const listRef = useRef<List>(null);
  const parentWrapperRef = useRef(null);

  useEffect(() => {
    setIsLoading(true);
    merchantFetch({
      url: countriesUrl,
      method: 'get',
    })
      .then(({ data }) => {
        const countries =
          data?.countries?.map((c) => ({ ...c, total_states: c.states.length })) || [];
        const { allCountries, countriesWithStates } = buildCountriesData(countries, zone);
        setCollapsibleCountries(countriesWithStates);
        setFilteredCountries(allCountries);
      })
      .catch((err) => {
        setError(err?.errors[0] || 'Something went wrong');
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [zone]);

  const _getRowHeight = ({ index }) => {
    const country = filteredCountries[index];
    if (country.visible_status === VISIBLE_STATES.NONE) {
      return 0;
    }
    const stateKeys = Object.keys(country.states);
    if (country.visible_status === VISIBLE_STATES.SOME && collapsibleCountries[country.code]) {
      return (
        virtualizedCache.current.defaultHeight *
        ((stateKeys.filter((s) => country.states[s].visible_status === VISIBLE_STATES.ALL).length ||
          0) +
          1)
      );
    }
    if (country.visible_status === 'all' && collapsibleCountries[country.code]) {
      return virtualizedCache.current.defaultHeight * ((stateKeys.length || 0) + 1);
    }
    return virtualizedCache.current.defaultHeight;
  };

  const handleCollapse = (code, index) => {
    collapsibleCountries[code] = !collapsibleCountries[code];
    setCollapsibleCountries(JSON.parse(JSON.stringify(collapsibleCountries)));
    listRef.current?.recomputeRowHeights(index);
  };

  const collapseAllCountries = (val) => {
    const newCollapsibleCountries = {};
    Object.keys(collapsibleCountries).forEach((key) => {
      newCollapsibleCountries[key] = val;
    });
    setCollapsibleCountries(newCollapsibleCountries);
  };

  const resetVisibileStatus = () => {
    setFilteredCountries(
      filteredCountries.map((c) => {
        c.visible_status = VISIBLE_STATES.ALL;
        if (Object.keys(c.states).length > 0) {
          Object.keys(c.states).forEach((state_code) => {
            c.states[state_code].visible_status = VISIBLE_STATES.ALL;
          });
        }
        return c;
      }),
    );
    collapseAllCountries(true);
    listRef.current?.recomputeRowHeights();
  };

  const updateParentSelectedStatus = (code, status, count = 1) => {
    if (status) {
      filteredCountries[code].total_selected += count;
      if (
        filteredCountries[code].total_selected === filteredCountries[code].total_selectable_children
      ) {
        filteredCountries[code].selected = true;
      }
    } else {
      filteredCountries[code].total_selected -= count;
      filteredCountries[code].selected = false;
    }
  };

  const handleSelectedZones = (item, status): void => {
    item.selected = status;
    const isParent = item.depth !== DEPTH_MAP.STATE;

    if (isParent) {
      // current item is a country
      if (item.depth === DEPTH_MAP.COUNTRY) {
        const selected_items =
          (status
            ? item.total_selectable_children - item.total_selected
            : item.total_selectable_children) || 1;
        updateParentSelectedStatus(item.parentIndex, status, selected_items);
        forAllStates(item, (state) => {
          state.selected = status;
        });
        updateTotalSelectedStatus(filteredCountries, item.index, status);
      } else if (item.name === GLOBAL_KEY) {
        forAllCountries(filteredCountries, status, (country) => (country.selected = status));
        updateTotalSelectedStatus(filteredCountries, 0, status);
      }
    } else {
      updateParentSelectedStatus(item.parentIndex, status);
      // commented below line to remove International option in popup, might need it in future
      updateParentSelectedStatus(0, status);
    }
    setFilteredCountries(JSON.parse(JSON.stringify(filteredCountries)));
  };

  //to search for a particular state/country in modal
  const handleSearch = (value) => {
    const searchText = value.toLowerCase();
    setSearchText(searchText);
    collapseAllCountries(true);
    if (searchText.length > 0) {
      const newFilteredCountries = filteredCountries.map((country) => {
        if (country.name.toLowerCase().includes(searchText)) {
          country.visible_status = 'all';
        } else if (Object.keys(country.states).length > 0) {
          /**
           * filtering out the states based on search, if search text is equal to
           * state code making the country visible in the zone modal else not
           */

          let hasSomeMatch = false;
          Object.keys(country.states).forEach((state_code) => {
            const { name } = country.states[state_code];
            if (name.toLowerCase().includes(searchText)) {
              country.states[state_code].visible_status = VISIBLE_STATES.ALL;
              hasSomeMatch = true;
            } else {
              country.states[state_code].visible_status = VISIBLE_STATES.NONE;
            }
          });
          if (hasSomeMatch) country.visible_status = VISIBLE_STATES.SOME;
          else country.visible_status = VISIBLE_STATES.NONE;
        } else {
          country.visible_status = VISIBLE_STATES.NONE;
        }
        return country;
      });
      setFilteredCountries(newFilteredCountries);
      listRef.current?.recomputeRowHeights();
    } else {
      resetVisibileStatus();
    }
  };

  const confirmZone = (zoneName) => {
    const locations = getLocationsPayload(filteredCountries, selectedZone);
    if (!locations.length) {
      showNotification({
        type: 'error',
        message: 'Select atleast one location to create a zone',
      });
    } else {
      const zonePayload: Zone = {
        name: zoneName,
        type: zoneType,
        locations,
      };
      if (zone?.id) zonePayload.id = zone.id;
      if (item_category_id) zonePayload.item_category_id = item_category_id;
      const actionFn = isEditMode ? updateZone : createZone;
      actionFn(zonePayload)
        .then(() => {
          const toastMessage = isEditMode ? 'Zone updated' : 'Zone created';
          showNotification({
            type: 'success',
            message: () => (
              <DisplayNotificationTxt notificationTxt={`${toastMessage} successfully`} />
            ),
          });
          closeModal();
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: err?.errors[0] || 'Something went wrong',
          });
        });
    }
  };

  const zoneName = useMemo(
    () => (isEditMode ? selectedZone?.name : ''),
    [isEditMode, selectedZone],
  );

  return (
    <SettingsModal
      header={MODAL_HEADER}
      variant="zone"
      searchPlaceholder="country, city, state"
      searchFn={handleSearch}
      entityName={zoneName}
      confirmAction={confirmZone}
      isOpen={isOpen}
      isLoading={loading || !!isZoneFetching}
      handleDismiss={closeModal}
      disableConfirmButton={loading || !!isZoneFetching}
    >
      {isLoading || isZoneFetching ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : error ? (
        <Text marginY="spacing.5" color="interactive.text.negative.subtle">
          {error}
        </Text>
      ) : Object.keys(filteredCountries).length === 0 ? (
        <div className="empty-text">
          <SearchIcon color="feedback.icon.neutral.intense" size="large" />
          <p>No results found</p>
        </div>
      ) : Object.keys(filteredCountries).length > 0 ? (
        <ModalWrapper data-testid="zone-modal" ref={parentWrapperRef}>
          <AutoSizer>
            {({ width, height }) => (
              <List
                ref={listRef}
                className="countries-virtualised-list"
                width={width}
                height={height}
                rowHeight={_getRowHeight}
                deferredMeasurementCache={virtualizedCache.current}
                rowCount={filteredCountries.length}
                rowRenderer={({ index, style, parent }) => {
                  const country = filteredCountries[index];
                  return (
                    <CellMeasurer
                      key={`country-${country.code}`}
                      cache={virtualizedCache.current}
                      parent={parent}
                      columnIndex={0}
                      rowIndex={index}
                    >
                      {({ registerChild }) => (
                        <div style={style} className="country" ref={registerChild}>
                          <ZoneItem
                            handleSelectedZones={handleSelectedZones}
                            isCountry
                            item={country}
                            searchText={searchText}
                            handleCollapse={handleCollapse}
                            collapsed={collapsibleCountries[country.code]}
                            zone={zone?.name}
                          />
                        </div>
                      )}
                    </CellMeasurer>
                  );
                }}
              />
            )}
          </AutoSizer>
        </ModalWrapper>
      ) : null}
    </SettingsModal>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

const Component: ({
  id,
  isOpen,
  mode,
  zone,
  countriesUrl,
  createZone,
  updateZone,
  closeModal,
  isZoneFetching,
}: Omit<ZoneModalProps, 'showNotification'>) => JSX.Element = connect(
  null,
  mapDispatchToProps,
)(ZoneModal);
export default Component;
