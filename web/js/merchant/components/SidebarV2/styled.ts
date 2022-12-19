import styled from 'styled-components';

export const SidebarContainer = styled.div`
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  bottom: 0;
  background-color: #2e3345;
  width: 248px;
  z-index: 1111;
`;

export const SidebarSection = styled.section`
  &:after {
    content: '';
    position: absolute;
    border: 1px solid #45495a;
    left: 20px;
    right: 20px;
  }
  a {
    display: block;
    line-height: 50px;
    text-align: center;
    padding: 10px;
  }
`;

export const Logo = styled.img`
  max-width: 100%;
  height: auto;
  display: inline-block;
  width: 145px;
  height: 35px;
`;

export const Items = styled.div`
  display: flex;
  flex-direction: column;
  gap: 2px;
`;

export const NavContent = styled.div`
  padding-left: 0;
  margin-bottom: 0;
  list-style: none;
`;

export const Navigation = styled.nav`
  padding: 15px 0 30px;
  overflow-y: auto;
  overflow-x: hidden;
  -ms-overflow-style: none;
  &::-webkit-scrollbar {
    display: none;
  }
`;

export const ExternalLink = styled.a`
  height: 29px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  line-height: 21px;
  color: #b4b6bc;
  position: relative;
  &:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
  }
`;
