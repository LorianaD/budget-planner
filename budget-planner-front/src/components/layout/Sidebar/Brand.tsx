// Sidebar/Brand.tsx — plus besoin d'importer texts.ts !
import type { BrandProps } from './types';
import styles from './Sidebar.module.css';
import { logo } from '../../../assets'

function Brand({ texts }: BrandProps) {
  return (
    <div className={styles['sidebar-brand']}>
      <div className={styles['sider-brand__logo']}>
        <img src={logo} alt="logo" />
      </div>
      <p className={styles['sidebar-brand__text']}>{texts.budgetName}</p>
    </div>
  );
}

export default Brand;