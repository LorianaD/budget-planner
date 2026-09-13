// Main/types.ts
export type MainVariant = 'dashboard' | 'auth' | 'public'; // ajoute tes variantes ici

export type MainProps = {
  variant: MainVariant;
  children: React.ReactNode;
};