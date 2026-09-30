// Button/types.ts
import type { ReactNode } from 'react';

export type ButtonType = 'button' | 'submit';

export type ButtonProps = {
  type: ButtonType;
  isLoading: boolean;
  loadingLabel: string;
  children: ReactNode;
};
