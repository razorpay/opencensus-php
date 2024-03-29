import styled from 'styled-components';

export const DataTableWrapper = styled.div<{ maxWidth?: string }>(
  ({ theme, maxWidth = '530px' }) => `
  max-width: ${maxWidth};
  display flex;
  flex-direction: column;
  
  .settings-table {
    border: 1px solid ${theme.colors.surface.border.gray.muted};
    border-collapse: separate;
    border-radius: ${theme.border.radius.small}px;
    margin-bottom: 0;
    th {
      background-color: #F5F6F7;
      border: none;
    }
    & > thead {
      &:first-child {
        & > tr {
          &:first-child {
            & > th {
              border-top: 0;
            }
          }
        }
      }
    }
    .magic-table-column {
      text-align: left;
      vertical-align: middle;
    }

    tr {
      &:nth-child(odd), &:nth-child(even) {
        background-color: ${theme.colors.surface.background.gray.intense};
      }
    }

    td {
      &.magic-table-column {
        padding: 5px 10px;
      }
    }
    .zone-actions {
      justify-content: flex-end;
      svg {
        & > path {
          fill: ${theme.colors.surface.background.primary.intense};
        }
      }
      .delete-button {
        margin-left: 10px;
        svg {
          & > path {
            fill: ${theme.colors.feedback.background.negative.intense};
          }
        }
      }
    }
  }
  .settings-table th:last-child,
  .settings-table td:last-child {
    text-align: right;
  }

  .single-column {
    th {
      &:last-child {
        text-align: left;
      }
    }
    td {
      &:last-child {
        text-align: left;
      }
    }
  }
`,
);

export const AddMoreButton = styled.p(
  ({ theme }) => `
  border: 1px solid ${theme.colors.surface.border.gray.muted};
  border-top: none;
  background: ${theme.colors.surface.background.gray.intense};
  border-bottom-left-radius: 2px;
  border-bottom-right-radius: 2px;
  padding: 12px 10px;
  color: ${theme.colors.surface.background.primary.intense};
  font-weight: 600;
  cursor pointer;
`,
);
