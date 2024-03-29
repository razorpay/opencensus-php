import styled from 'styled-components';

const COLORS = {
  success: '#008659',
  failed: '#b25757de',
  processing: '#bd7a03',
};

const Container = styled.div`
  width: 100%;
  padding: 40px 24px 0px;
  display: flex;
  flex-direction: column;
  overflow-y: hidden;
  border-top: 3px solid transparent;
  border-radius: 3px;

  &.cont--success {
    border-color: ${COLORS.success};
  }
  &.cont--error {
    border-color: ${COLORS.failed};
  }
  &.cont--warn {
    border-color: #0b70e7de;
  }
  &.cont--processing {
    border-color: ${COLORS.processing};
  }

  .payment_suc_fail {
    &__header {
      > div:first-child {
        flex-grow: 1;
        padding-right: 40px;
      }
    }
    &__msg--multiple {
      .payment_suc_fail__msg {
        .title {
          font-size: 16px;
        }
        .header-icon {
          font-size: 16px;
        }
      }
      .payment_suc_fail__header + hr {
        display: none;
      }
    }
  }

  .title {
    font-weight: 600;
    font-size: 20px;
    line-height: 24px;
    color: rgba(22, 47, 86, 0.87);
  }

  .subtitle {
    font-size: 14px;
    line-height: 22px;
    color: rgba(22, 47, 86, 0.76);
  }

  table {
    margin: 16px 0px;
    td,
    th {
      padding: 4px 16px 4px 0px;
      min-width: 170px;
    }
    th {
      font-size: 13px;
      line-height: 16px;
      color: #7a8898;
      font-weight: normal;
    }
    td {
      font-weight: 600;
      font-size: 14px;
      line-height: 18px;
      color: rgba(22, 47, 86, 0.87);
    }
  }

  footer {
    margin-top: auto;
    .Button--transparent.Button {
      padding: 0px;
    }
    > div:first-child {
      margin: 0px;
    }
  }

  .white-nowrap {
    white-space: nowrap;
  }
`;

const Seperator = styled.hr`
  margin: 20px 0;
  border-top: 1px solid #031c3a14;
`;

const ICONS = {
  success: 'i-success-icon',
  failed: 'i-info-alt',
  processing: 'i-clock',
};
const ICONS_COLOR = {
  [ICONS.success]: COLORS.success,
  [ICONS.failed]: COLORS.failed,
  [ICONS.processing]: COLORS.processing,
};
const HeaderIcon = styled.i.attrs((props) => ({
  className: `i header-icon text-xl ${props.name}`,
}))`
  color: ${(props) => ICONS_COLOR[props.name]};
`;

export { Seperator, Container, ICONS, HeaderIcon };
