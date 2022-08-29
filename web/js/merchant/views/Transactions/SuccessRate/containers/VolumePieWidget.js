import React from 'react';
import { connect } from 'react-redux';
import { Pie } from 'react-chartjs-2';
import compact from 'lodash/compact';
import isEmpty from 'lodash/isEmpty';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Spinner from 'common/ui/Spinner';
import StyledHeader from '../components/StyledHeader';
import NoDataMessage from '../components/NoDataMessage';
import { pieChartOptions as options, piePlugins as plugins } from '../chartConfig';
import { getPieChartData } from '../helper';
import { TAG_MAP, TAG_OVERALL_MAP } from '../constants';

const renderInfoCard = ({ name, successful, total, sr } = {}, index) => {
  if (name === 'others' && !sr) return null;
  const label = (TAG_MAP[name] ?? name) || '--';
  return (
    <div key={`${name}___${index}`} className="col-md-4 info-card">
      <StyledHeader text={label} />
      <div className="info-card__label">
        <p className="label-text">Successful / Total Attempts</p>
      </div>
      <div className="info-card__value">
        <p>
          <span className="highlight">{successful}</span>/{total} ({sr}%)
        </p>
      </div>
    </div>
  );
};

const VolumePieWidget = (props) => {
  const { isLoading, tab = {} } = props;
  const { group_by = '', data } = tab;
  const groupData = data?.groups?.[group_by] ?? [];
  const pieChartData = getPieChartData(groupData);
  const compactData = compact(pieChartData?.datasets?.[0]?.data);

  if (isLoading) {
    return (
      <div className="box-widget volume-container">
        <StyledHeader text="Volume of attempts" />
        <Spinner />
      </div>
    );
  }

  return (
    <div className="box-widget volume-container">
      <div className="row">
        <div className="col-sm-12 col-md-5">
          <div className="chart-col">
            <StyledHeader text="Volume of attempts" />
            {!isEmpty(compactData) && (
              <div className="pie-chart">
                <Pie data={pieChartData} options={options} plugins={plugins} />
              </div>
            )}
          </div>
        </div>
        <div className="col-sm-12 col-md-7">
          <div className="row info-col">
            {!isLoading && data?.sr && (
              <div className="col-md-4 info-card">
                {isLoading ? (
                  <PlaceholderLoader style={{ marginBottom: '10px' }} />
                ) : (
                  <StyledHeader text={TAG_OVERALL_MAP[group_by]} />
                )}
                {isLoading ? (
                  <PlaceholderLoader />
                ) : (
                  <div className="info-card__label">
                    <p className="label-text">Successful / Total Attempts</p>
                  </div>
                )}
                {isLoading ? (
                  <PlaceholderLoader />
                ) : (
                  <div className="info-card__value">
                    <p>
                      <span className="highlight">{data?.successful}</span>/{data?.total} (
                      {data?.sr}
                      %)
                    </p>
                  </div>
                )}
              </div>
            )}
            {!isEmpty(groupData) && groupData?.map(renderInfoCard)}
          </div>
        </div>
      </div>
      {isEmpty(compactData) && <NoDataMessage title="No data available." />}
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { isLoading, tabLoading, activeTab, tabs } = successRate;
  return {
    activeTab,
    isLoading: isLoading || tabLoading,
    tab: tabs[activeTab],
  };
};

export default connect(mapStateToProps, null)(VolumePieWidget);
