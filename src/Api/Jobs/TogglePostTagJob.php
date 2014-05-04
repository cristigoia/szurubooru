<?php
class TogglePostTagJob extends AbstractPostJob
{
	public function execute()
	{
		$tagName = $this->getArgument(self::TAG_NAME);
		$enable = boolval($this->getArgument(self::STATE));
		$post = $this->post;

		$tags = $post->getTags();

		if ($enable)
		{
			$tag = TagModel::findByName($tagName, false);
			if ($tag === null)
			{
				$tag = TagModel::spawn();
				$tag->setName($tagName);
				TagModel::save($tag);
			}

			$tags []= $tag;
		}
		else
		{
			foreach ($tags as $i => $tag)
				if ($tag->getName() == $tagName)
					unset($tags[$i]);
		}

		$post->setTags($tags);
		PostModel::save($post);
		TagModel::removeUnused();

		if ($enable)
		{
			Logger::log('{user} tagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
		}
		else
		{
			Logger::log('{user} untagged {post} with {tag}', [
				'user' => TextHelper::reprUser(Auth::getCurrentUser()),
				'post' => TextHelper::reprPost($post),
				'tag' => TextHelper::reprTag($tag)]);
		}

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::EditPostTags,
			Access::getIdentity($this->post->getUploader()));
	}
}
