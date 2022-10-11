import React from 'react';
import Pointer from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/common/Pointer';

const InfoModal = ({ heading, instructions }) => (
  <>
    <p className="modal-heading">{heading}</p>
    <div className="modal-info">
      {instructions.map((item, index) => (
        <div className="row display-flex info-points" key={index}>
          <div className="col-sm-1 no-padding">
            <Pointer />
          </div>
          <div className="col-sm-11 no-padding">
            {item.customPoints ? (
              <p className="info-text">
                {item.points.map((point, index) =>
                  point.type === 'link' ? (
                    React.createElement(
                      'a',
                      {
                        href: point.url,
                        key: index,
                        target: '_blank',
                        className: 'info-link',
                        rel: 'noreferrer noopener',
                      },
                      point.text,
                    )
                  ) : (
                    <span key={index}>{point.text}</span>
                  ),
                )}
              </p>
            ) : (
              <p className="info-text">{item.point}</p>
            )}
          </div>
        </div>
      ))}
    </div>
  </>
);

export default InfoModal;
