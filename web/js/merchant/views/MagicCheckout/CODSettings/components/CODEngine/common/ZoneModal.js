import React, { useEffect, useState, useRef } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Spinner from 'common/ui/Spinner';
import { SearchIcon } from '@razorpay/blade/components';
import AutoSizer from 'react-virtualized/dist/commonjs/AutoSizer';
import List from 'react-virtualized/dist/commonjs/List';
import CellMeasurer, { CellMeasurerCache } from 'react-virtualized/dist/commonjs/CellMeasurer';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import ZoneItem from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/ZoneItem';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { createZone, updateZone } from 'merchant/reducers/magicCheckout/codEngine/action';

import { MODAL_MODES, GLOBAL_KEY } from 'merchant/views/MagicCheckout/CODSettings/constants';
import {
  DEPTH_MAP,
  buildCountriesData,
  forAllCountries,
  forAllStates,
  getLocationsPayload,
  updateTotalSelectedStatus,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/zoneUtils';
import { merchantFetch } from 'merchant/utils/ajax';

const SettingModal = lazy(() =>
  import(
    /* webpackChunkName: "ZoneSettings" */ 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SettingModal'
  ),
);

function ZoneModal({ closeModal, mode, id, showNotification, zones, createZone, updateZone }) {
  const zone = zones.find((z) => z.id === id);
  const editMode = mode === MODAL_MODES.EDIT;
  const MODAL_HEADER = `${editMode ? 'Edit' : 'Create'} COD zones`;
  const [searchText, setSearchText] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  const [filteredCountries, setFilteredCountries] = useState([]);
  const [collapsibleCountries, setCollapsibleCountries] = useState({});
  const virtualizedCache = useRef(
    new CellMeasurerCache({
      fixedWidth: true,
      defaultHeight: 60,
    }),
  );
  const listRef = useRef(null);
  const parentWrapperRef = useRef(null);

  useEffect(() => {
    setIsLoading(true);
    merchantFetch({
      url: '1cc/shipping/cod/countries',
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
  }, []);

  const collapseAllCountries = (val) => {
    const newCollapsibleCountries = {};
    // eslint-disable-next-line guard-for-in
    for (const key in collapsibleCountries) {
      newCollapsibleCountries[key] = val;
    }
    setCollapsibleCountries(newCollapsibleCountries);
  };
  const resetVisibileStatus = () => {
    setFilteredCountries(
      filteredCountries.map((c) => {
        c.visible_status = 'all';
        if (Object.keys(c.states).length > 0) {
          Object.keys(c.states).forEach((state_code) => {
            c.states[state_code].visible_status = 'all';
          });
        }
        return c;
      }),
    );
    collapseAllCountries(true);
    listRef.current.recomputeRowHeights();
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

  const handleSelectedZones = (item, status) => {
    item.selected = status;
    const isParent = item.depth !== DEPTH_MAP.STATE;

    if (isParent) {
      // country
      if (item.depth === DEPTH_MAP.COUNTRY) {
        updateParentSelectedStatus(
          item.parentIndex,
          status,
          (status
            ? item.total_selectable_children - item.total_selected
            : item.total_selectable_children) || 1,
        );
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
      // updateParentSelectedStatus(0, status);
    }
    setFilteredCountries(JSON.parse(JSON.stringify(filteredCountries)));
  };
  const handleSearch = (value) => {
    const searchText = value.toLowerCase();
    setSearchText(searchText);
    collapseAllCountries(true);
    if (searchText.length > 0) {
      const newFilteredCountries = filteredCountries.map((country) => {
        if (country.name.toLowerCase().includes(searchText)) {
          country.visible_status = 'all';
        } else if (Object.keys(country.states).length > 0) {
          let someMatch = false;
          Object.keys(country.states).forEach((state_code) => {
            const { name } = country.states[state_code];
            if (name.toLowerCase().includes(searchText)) {
              country.states[state_code].visible_status = 'all';
              someMatch = true;
            } else {
              country.states[state_code].visible_status = 'none';
            }
          });
          if (someMatch) country.visible_status = 'some';
          else country.visible_status = 'none';
        } else {
          country.visible_status = 'none';
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
    const locations = getLocationsPayload(filteredCountries, zone);
    if (!locations.length) {
      showNotification({
        type: 'error',
        message: 'Select atleast one location to create a zone',
      });
    } else {
      const zonePayload = {
        name: zoneName,
        type: 'cod',
        locations,
      };
      if (zone?.id) zonePayload.id = zone.id;
      const actionFn = editMode ? updateZone : createZone;
      actionFn(zonePayload)
        .then(() => {
          showNotification({
            type: 'success',
            message: () => (
              <DisplayNotificationTxt
                notificationTxt={`${editMode ? 'Zone updated' : 'Zone created'} successfully`}
              />
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
  const _getRowHeight = ({ index }) => {
    const country = filteredCountries[index];
    if (country.visible_status === 'none') {
      return 0;
    }
    const stateKeys = Object.keys(country.states);
    if (country.visible_status === 'some' && collapsibleCountries[country.code]) {
      return (
        virtualizedCache.current.defaultHeight *
        ((stateKeys.filter((s) => country.states[s].visible_status === 'all').length || 0) + 1)
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
    listRef.current.recomputeRowHeights(index);
  };
  if (isLoading) {
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  return (
    <SuspenseWithLoader type="full">
      <SettingModal
        header={MODAL_HEADER}
        variant="Zone"
        placeholder="country, city, state"
        searchFn={handleSearch}
        name={editMode ? zone.name : ''}
        confirmAction={confirmZone}
        className="cod-config-modal"
        type="zone"
      >
        {error ? (
          <p>{error}</p>
        ) : Object.keys(filteredCountries).length === 0 ? (
          <div className="empty-text">
            <SearchIcon size="large" />
            <p>No results found</p>
          </div>
        ) : (
          <div
            data-testid="zone-modal"
            className="zone-modal-virtualized-container"
            ref={parentWrapperRef}
          >
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
          </div>
        )}
      </SettingModal>
    </SuspenseWithLoader>
  );
}

const mapStateToProps = (state) => ({
  zones: state.magicCODEngine.zones,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
      createZone,
      updateZone,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ZoneModal);
