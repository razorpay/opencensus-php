import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { observer } from 'mobx-react';
import { openModal } from 'razorx/components/Modal';
import Collection from 'razorx/model/collection';
import { splitzFetch } from 'razorx/helpers/fetch';
import AddEditProject from './AddEditProject';
import ProjectList from './ProjectList';
import ProjectDetails from './ProjectDetails';
import { PROJECT_LIST } from './constants';

@withRouter
@observer
export default class Projects extends React.Component {
  collection = new Collection({
    isSplitz: true,
    fetchFn: splitzFetch,
    data: {
      url: PROJECT_LIST,
    },
  });

  showAddEditProject = () => openModal(<AddEditProject collection={this.collection} />);

  render() {
    const projectId = this.props.match.params.id;

    return (
      <React.Fragment>
        <div className="page-center" id="page-logo">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            style={{ width: 80, fill: 'rgba(255,255,255,0.06)' }}
          >
            <path d="M 8 3 L 8 5 L 9 5 L 9 10 L 3.4316406 17.773438 C 3.1656406 18.112437 3 18.535 3 19 C 3 20.105 3.895 21 5 21 L 19 21 C 20.105 21 21 20.105 21 19 C 21 18.535 20.834359 18.112437 20.568359 17.773438 L 15 10 L 15 5 L 16 5 L 16 3 L 8 3 z M 11 5 L 13 5 L 13 9 L 11 9 L 11 5 z M 10.744141 11 L 13.255859 11 L 18.943359 18.9375 L 18.972656 18.966797 L 19 19 L 5.0058594 19.007812 L 5.03125 18.972656 L 5.0566406 18.9375 L 10.744141 11 z M 13 13 A 1 1 0 0 0 12 14 A 1 1 0 0 0 13 15 A 1 1 0 0 0 14 14 A 1 1 0 0 0 13 13 z M 10.5 15 A 1.5 1.5 0 0 0 9 16.5 A 1.5 1.5 0 0 0 10.5 18 A 1.5 1.5 0 0 0 12 16.5 A 1.5 1.5 0 0 0 10.5 15 z" />
          </svg>
          <div>
            Split<strong>Z</strong>
          </div>
        </div>

        <div className="parent-container features-container">
          <div className="header">
            <span className="title">Projects</span>
            <div className="btn-group">
              <button className="btn btn--primary" onClick={this.showAddEditProject}>
                + New Project
              </button>
            </div>
          </div>
          <div className="container-group">
            <ProjectList collection={this.collection} />
            <ProjectDetails projectId={projectId} collection={this.collection} />
          </div>
        </div>
      </React.Fragment>
    );
  }
}

Projects.propTypes = {
  match: PropTypes.object.isRequired,
};
