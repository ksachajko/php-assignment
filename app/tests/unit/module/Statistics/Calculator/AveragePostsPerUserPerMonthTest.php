<?php

declare(strict_types=1);

namespace Tests\unit\module\Statistics\Calculator;

use PHPUnit\Framework\TestCase;
use SocialPost\Dto\SocialPostTo;
use Statistics\Calculator\AveragePostsPerUserPerMonth;
use Statistics\Calculator\CalculatorInterface;
use Statistics\Dto\ParamsTo;
use Statistics\Dto\StatisticsTo;
use Statistics\Enum\StatsEnum;

class AveragePostsPerUserPerMonthTest extends TestCase
{
    private CalculatorInterface $calculator;

    protected function setUp(): void
    {
        $params = (new ParamsTo())
            ->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
            ->setStartDate(new \DateTime('2023-01-01 00:00:00.000000'))
            ->setEndDate(new \DateTime('2023-02-28 23:59:59.000000'));

        $this->calculator = new AveragePostsPerUserPerMonth();
        $this->calculator->setParameters($params);
    }

    public function testCalculateStatisticsNoPosts(): void
    {
        $this->assertEquals($this->getEmptyStatisticsTo(), $this->calculator->calculate());
    }

    public function testCalculateStatisticsIgnorePostsOutsideDateRange(): void
    {
        $post1 = (new SocialPostTo())
            ->setDate(new \DateTime('2000-01-01 00:00:00.000000'));
        $post2 = (new SocialPostTo())
            ->setDate(new \DateTime('2025-01-01 00:00:00.000000'));

        $this->calculator->accumulateData($post1);
        $this->calculator->accumulateData($post2);

        $this->assertEquals($this->getEmptyStatisticsTo(), $this->calculator->calculate());
    }

    /**
     * @dataProvider getDataForTest
     */
    public function testCalculateStatistics(StatisticsTo $expected, array $posts): void
    {
        foreach ($posts as $post) {
            $this->calculator->accumulateData($post);
        }

        $this->assertEquals($expected, $this->calculator->calculate());
    }

    private function getDataForTest(): array
    {
        return [
            [
                $this->getEmptyStatisticsTo()->addChild((new StatisticsTo())
                    ->setName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setUnits('posts')
                    ->setSplitPeriod('January, 2023')
                    ->setValue(1.0)
                ),
                [
                    (new SocialPostTo())
                        ->setAuthorId('author1')
                        ->setDate(new \DateTime('2023-01-10 00:00:00.000000')),
                    (new SocialPostTo())
                        ->setAuthorId('author2')
                        ->setDate(new \DateTime('2023-01-10 00:00:00.000000')),
                ]
            ],
            [
                $this->getEmptyStatisticsTo()
                    ->addChild((new StatisticsTo())
                        ->setName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                        ->setUnits('posts')
                        ->setSplitPeriod('January, 2023')
                        ->setValue(1.0)
                    )->addChild((new StatisticsTo())
                        ->setName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                        ->setUnits('posts')
                        ->setSplitPeriod('February, 2023')
                        ->setValue(1.5)
                    ),
                [
                    (new SocialPostTo())
                        ->setAuthorId('author1')
                        ->setDate(new \DateTime('2023-01-10 00:00:00.000000')),
                    (new SocialPostTo())
                        ->setAuthorId('author2')
                        ->setDate(new \DateTime('2023-01-10 00:00:00.000000')),
                    (new SocialPostTo())
                        ->setAuthorId('author1')
                        ->setDate(new \DateTime('2023-02-10 00:00:00.000000')),
                    (new SocialPostTo())
                        ->setAuthorId('author2')
                        ->setDate(new \DateTime('2023-02-10 00:00:00.000000')),
                    (new SocialPostTo())
                        ->setAuthorId('author2')
                        ->setDate(new \DateTime('2023-02-10 00:00:00.000000')),
                ]
            ],
        ];
    }

    private function getEmptyStatisticsTo(): StatisticsTo
    {
        return (new StatisticsTo())
            ->setUnits('posts')
            ->setName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH);
    }
}
