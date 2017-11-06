import React from 'react';
import BaseToggler from 'rzp/ui/Toggler/BaseToggler';

/*
 * Description:
 * Content toggler, which displays only the first child passed to the
 * component by default with an arrow mark, on clicking it will display
 * the rest of the components, with the arrow mark re-aligned
 *
 * Usage:
 * <ContentToggler>
 *  <span>Address</span>
 *  <span>Address Line 1</span>
 *  <span>Address Line 2</span>
 *  <span>Address Line 3</span>
 * </ContentToggler>
 */
export default class ContentToggler extends BaseToggler {
  render() {
    const children = Array.isArray(this.props.children)
      ? this.props.children
      : [this.props.children];

    if (children.length === 0) {
      return;
    }

    const hasMoreContent = children.length > 1,
      lessContent = children[0],
      moreContent = children.slice(1),
      hasMore = moreContent.length > 0,
      showMore = this.state.show && hasMore;

    return (
      <div className="rzp-content-toggler">
        <div className="less-content" onClick={this.toggle}>
          <div className="less-content-wrapper text-primary noselect">
            {lessContent}
          </div>
          {hasMore && (
            <div className="less-content-arrow text-primary">
              <i
                className={'icon icon-chevron-' + (showMore ? 'up' : 'down')}
              />
            </div>
          )}
        </div>
        {showMore && <div className="more-content">{moreContent}</div>}
      </div>
    );
  }
}
