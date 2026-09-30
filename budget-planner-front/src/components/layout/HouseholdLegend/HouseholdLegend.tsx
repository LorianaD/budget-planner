// HouseholdLegend/HouseholdLegend.tsx
import type { HouseholdLegendProps } from './types';
import styles from './HouseholdLegend.module.css';

function HouseholdLegend({ members }: HouseholdLegendProps) {
  return (
    <ul className={styles['household-legend']}>
      {members.map((member) => (
        <li className={styles['household-legend__item']} key={member.name}>
          <span
            className={styles['household-legend__dot']}
            style={{ backgroundColor: member.colorVariable }}
          ></span>
          <span className={styles['household-legend__name']}>{member.name}</span>
        </li>
      ))}
    </ul>
  );
}

export default HouseholdLegend;
