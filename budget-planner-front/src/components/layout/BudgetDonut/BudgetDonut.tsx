// BudgetDonut/BudgetDonut.tsx
import styles from './BudgetDonut.module.css';

function BudgetDonut() {
  return (
    <div
      className={styles['budget-donut']}
      role="img"
      aria-label="Répartition du budget selon la règle 50/30/20"
    ></div>
  );
}

export default BudgetDonut;
