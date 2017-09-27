<?php

class desc_similar_tag_db
{
	const SIMILAR_TAG_IDS = 'similar_tag_ids';
	const SIMILAR_TAG_WORDS = 'similar_tag_words';
	const WORDID = 'wordid';
	const WORD = 'word';
	const SIMILAR_COUNT = 5;

	/**
	 * 全てのタグの類似タグを更新
	 * @return int	処理件数
	 */
	public function update_all_similar_gats()
	{
		$cnt = 0;
		$all_tags = $this->get_all_tags();
		foreach ($all_tags as $tag) {
			$sim_tags = $this->get_similar_tags($tag, self::SIMILAR_COUNT);
			$this->set_similar_tags($tag, $sim_tags);
			$cnt++;
		}
		return $cnt;
	}

	/**
	 * 類似タグWORDをMETAテーブルから取得
	 * @param  string $word タグのword
	 * @return string        類似タグ、カンマ区切り
	 */
	public function get_similar_tag_words($word)
	{
		$sql = "SELECT content
FROM ^tagmetas
WHERE tag=$ AND title=$
";
		$result = qa_db_query_sub($sql, $word, self::SIMILAR_TAG_WORDS);
		if ($result->num_rows > 0) {
			return qa_db_read_one_value($result);
		} else {
			return '';
		}

	}

	/**
	 * 類似タグを取得
	 * @param  int $wordid タグのwordid
	 * @param  int $cnt    取得する類似タグの数
	 * @return array     類似タグのwordid
	 */
	public function get_similar_tags($wordid, $cnt)
	{
		$result = array();

		$sql = "SELECT w.word
FROM ^posttags as p
INNER JOIN ^words as w
ON p.wordid = w.wordid
WHERE p.postid in (
SELECT postid FROM ^posttags WHERE wordid = #
)
AND p.wordid != #
GROUP BY p.wordid
ORDER BY count(p.wordid) DESC
LIMIT #";

		return qa_db_read_all_values(qa_db_query_sub($sql, $wordid, $wordid, $cnt));

	}

	/**
	 * 類似タグをテーブルに保存する
	 * @param int $wordid タグのwordid
	 * @param array $tags   類似タグのwordid
	 */
	public function set_similar_tags($wordid, $tags)
	{
		if(isset($wordid)) {
			$word = $this->wordid_to_word($wordid);
			if (count($tags) > 0) {
				if ($this->exists_similar_tags_meta($word, self::SIMILAR_TAG_WORDS)) {
					$this->update_similar_tags($word, self::SIMILAR_TAG_WORDS, $tags);
				} else {
					$this->create_similar_tags($word, self::SIMILAR_TAG_WORDS, $tags);
				}
			}
		}
	}

	/**
	 * ポストに付いている全てのタグを取得
	 * @return array タグのwordid
	 */
	private function get_all_tags()
	{

		$sql = "SELECT DISTINCT wordid FROM ^posttags";

		return qa_db_read_all_values(qa_db_query_sub($sql));
	}

	/**
	 * 類似タグを新規作成
	 * @param  int $wordid タグのwordid
	 * @param  array $tags 類似タグのwordid
	 */
	private function create_similar_tags($wordid, $title, $tags)
	{
		qa_db_query_sub(
			"INSERT INTO ^tagmetas (tag, title, content)
VALUES ($, $, $)
",
			$wordid, $title, implode(',', $tags)
		);

	}

	/**
	 * 類似タグを更新
	 * @param  int $wordid タグのwordid
	 * @param  array $tags   類似タグのwordidon]
	 */
	private function update_similar_tags($wordid, $title, $tags)
	{
		qa_db_query_sub(
			"UPDATE ^tagmetas
SET content=$
WHERE tag=$ AND title=$
",
			implode(',', $tags), $wordid, $title
		);
	}

	/**
	 * テーブルに類似タグが保存されているか
	 * @param  int $wordid タグのwordid
	 * @return boolean         保存されていればtrue
	 */
	private function exists_similar_tags_meta($wordid, $title)
	{
		$sql = "SELECT count(*) as cnt
FROM ^tagmetas WHERE tag = $ AND title = $
";
		$result = qa_db_read_one_value(qa_db_query_sub($sql, $wordid, $title));
		if (isset($result) && $result > 0) {
			return true;
		}
		return false;
	}

	/**
	 * wordidでwordを取得
	 * @param  int $wordid wordid
	 * @return string         word
	 */
	private function wordid_to_word($wordid = null)
	{
		if (!isset($wordid)) return '';

		$sql = "SELECT word FROM ^words WHERE wordid = #";

		return qa_db_read_one_value(qa_db_query_sub($sql, $wordid));
	}

}
