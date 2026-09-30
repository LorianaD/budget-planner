// HouseholdLegend/types.ts
export type HouseholdMember = {
  name: string;
  colorVariable: string;
};

export type HouseholdLegendProps = {
  members: HouseholdMember[];
};
