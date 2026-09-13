// Sidebar/types.ts
export type SidebarBrandTexts = {
  budgetName: string;
};

export type SidebarNavItem = {
  label: string;
  link: string;
};

export type SidebarNavTexts = {
  item: SidebarNavItem[];
};

export type SidebarTexts = {
  brand: SidebarBrandTexts;
  nav: SidebarNavTexts;
};

export type BrandProps = {
  texts: SidebarBrandTexts;
};

export type NavProps = {
  texts: SidebarNavTexts;
};