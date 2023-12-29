<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/19/2015
 * @time: 10:11 AM
 */

namespace VertigoLabs\TsQuery;

use Base\BaseORMTestCase;
use TsVector\Fixture\Article;

class TsRankTest extends BaseORMTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		$this->setUpSchema([Article::class]);

		foreach ($this->articleProvider() as $articleData) {
			$article = new Article();
			$article->setTitle($articleData[0]);
			$article->setBody($articleData[1]);
			$this->em->persist($article);
		}
		$this->em->flush();
	}

	/**
	 * @test
	 * @dataProvider searchDataProvider
	 */
	public function shouldReturnRank($queryStr, $field, $numberFound)
	{
		$query = $this->em->createQuery('SELECT a, tsrank(a.'.$field.',:searchQuery) as rank FROM TsVector\\Fixture\\Article a WHERE tsquery(a.'.$field.',:searchQuery) = true');
		$query->setParameter('searchQuery',$queryStr);
		$results = $query->getArrayResult();

		$this->assertEquals($numberFound, count($results));
		foreach($results as $result) {
			$this->assertArrayHasKey( 'rank', $result );
		}
	}

	/**
	 * @test
	 * @dataProvider phraseSearchDataProvider
	 */
	public function shouldReturnCoverDensityRank($queryStr, $field, $numberFound)
	{
		$query = $this->em->createQuery('SELECT a, tsrankcd(a.'.$field.',:searchQuery) as rank FROM TsVector\\Fixture\\Article a WHERE tsquery(a.'.$field.',:searchQuery) = true');
		$query->setParameter('searchQuery',$queryStr);
		$results = $query->getArrayResult();

		$this->assertEquals($numberFound, count($results));
		foreach($results as $result) {
			$this->assertArrayHasKey( 'rank', $result );
		}
	}

	public function articleProvider()
	{
		return [
			['Test Article One', 'This is a test article used for running unit tests. It contains a couple keywords such as Elephant, Dolphin, Kitten, Giraffe, and Baboon.'],
			['Baboons Invade Seaworld', 'In a crazy turn of events a pack a rabid red baboons invade Seaworld. Officials say that the Dolphins are being held hostage'],
			['Elephants learn to fly', 'Yesterday several witnesses claim to have seen a seen a number of purple elephants flying through the sky over the downtown area.'],
			['Giraffes Shorter Than Experts Though','A recent study has shown that giraffes are actually much shorter than researchers previously believed. "What we didn\'t realize was that the giraffes were actually always surrounded by other really short object" says one official.'],
			['Green Kittens not as cute as believed','A recent uncovering of an underground "cat mafia" has found new evidence that the media is being paid to over-exaggerate the cuteness of kittens who have been painted green.'],
			['Test Article Two', 'This is another test article used for running unit tests. This article has color based keywords such as; Blue, Green, Red, and Yellow.']
		];
	}

	public function searchDataProvider()
	{
		return [
			['dolphins | elephant','body', 3],
			['Dolphins','body', 2],
			['Dolphins','title', 0],
			['Dolphins | seaworld','title', 1],
			['Dolphins | seaworld','body', 2],
			['Giraffe','body', 2],
		];
	}

	public function phraseSearchDataProvider()
	{
		return [
			['giraffes','body', 2],
			['kittens','body', 2],
			['cats','body', 1],
		];
	}
}