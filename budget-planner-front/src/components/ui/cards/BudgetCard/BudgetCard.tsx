import { cx } from "../../../../utils";
import Badge from "../../Badge/Badge";
import styles from "./BudgetCard.module.css";
import type { BudgetCardsProps } from "./types";

function BudgetCard({titleBadge, description, variant, percent, value, totalValue}: BudgetCardsProps) {
    return (
        <div className={styles[`budget-card`]}>
            <div className={styles["budget-card__top"]}>
                <Badge text={titleBadge} variant={variant}/>
                <p className={styles["budget-card__texts"]}>
                    {percent}
                </p>
            </div>

            <div className={styles["badget-card__body"]}>
                <div className={styles["badget-card__compare"]}>
                    <p>{value} € / {totalValue} €</p>
                </div>

                <div className={cx(styles, `badge-card__progress`, `badge-card__progress--${variant}`)}></div>                
            </div>

            <div className={styles["badge-card__footer"]}>
                <p className={styles["budget-card__texts"]}>
                    {description}
                </p>
            </div>
        </div>
    )
}

export default BudgetCard;