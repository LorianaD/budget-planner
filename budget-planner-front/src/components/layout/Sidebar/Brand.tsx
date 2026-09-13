// Sidebar/Brand.tsx — plus besoin d'importer texts.ts !
import type { BrandProps } from './types';
import styles from './Sidebar.module.css'; 

function Brand({ texts }: BrandProps) {
  return (
    <div className={styles['sidebar-brand']}>
      <p>budget-planner</p>
      <p className={styles['sidebar-brand__text']}>{texts.budgetName}</p>
    </div>
  );
}

export default Brand;