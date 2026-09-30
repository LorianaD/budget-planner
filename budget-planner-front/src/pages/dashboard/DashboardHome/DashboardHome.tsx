import { SectionCards, SectionContainer, SectionTopbar } from "../../../components/ui";
import { BudgetCard, CardContainer } from "../../../components/ui/cards";
import { DASHBOARDHOME } from "./texts";

// pages/dashboard/DashboardHome.tsx
function DashboardHome() {
  const page = DASHBOARDHOME;
  const classPage = 'home';

  const budgetCards = page.BudgetCards;

  return (
    <SectionContainer variant={classPage}>
      <SectionTopbar variant={classPage} title={page.Topbar.title} description={page.Topbar.description} />
      <SectionCards variant="budget">
        {budgetCards.map((card) => (
          <BudgetCard
            key={card.name}
            titleBadge={card.title}
            description={card.description}
            variant={card.variant}
            percent={card.valuePercent}
            value={card.value}
            totalValue={card.totalValue}
          />
        ))}
      </SectionCards>
      <SectionCards variant="budget">
        <CardContainer title={page.MonthAllocation.title} variant="">
          value
        </CardContainer>
      </SectionCards>
    </SectionContainer>
  );
}

export default DashboardHome;