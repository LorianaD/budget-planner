import type { BadgeProps } from "./types";
import styles from './Badge.module.css';
import { cx } from "../../../utils";

function Badge({text, variant}: BadgeProps) {
    return (
        <div className={cx(styles, "badge", `badge--${variant}`)}>
            {text}
        </div>
    )
}

export default Badge;