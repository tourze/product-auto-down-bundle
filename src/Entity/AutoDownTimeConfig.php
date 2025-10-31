<?php

namespace Tourze\ProductAutoDownBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\ProductAutoDownBundle\Repository\AutoDownTimeConfigRepository;
use Tourze\ProductCoreBundle\Entity\Spu;

#[ORM\Table(name: 'product_auto_down_time_config', options: ['comment' => '商品自动下架时间配置'])]
#[ORM\Entity(repositoryClass: AutoDownTimeConfigRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_SPU_ID', columns: ['spu_id'])]
class AutoDownTimeConfig implements \Stringable
{
    use BlameableAware;
    use TimestampableAware;

    /** @phpstan-ignore-next-line property.unusedType */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[Assert\NotNull(message: 'SPU不能为空')]
    #[ORM\ManyToOne(targetEntity: Spu::class)]
    #[ORM\JoinColumn(name: 'spu_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Spu $spu = null;

    #[Groups(groups: ['admin_curd', 'restful_read'])]
    #[Assert\NotNull(message: '自动下架时间不能为空')]
    #[Assert\Type(type: '\DateTimeInterface', message: '自动下架时间必须为有效的日期时间')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '自动下架时间'])]
    private ?\DateTimeInterface $autoTakeDownTime = null;

    #[Groups(groups: ['admin_curd', 'restful_read'])]
    #[Assert\NotNull(message: '状态不能为空')]
    #[Assert\Type(type: 'bool', message: '状态必须为布尔值')]
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否有效'])]
    private bool $isActive = true;

    public function __toString(): string
    {
        return sprintf('SPU-%d 自动下架于 %s',
            $this->spu?->getId() ?? 0,
            $this->autoTakeDownTime?->format('Y-m-d H:i:s') ?? ''
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSpu(): ?Spu
    {
        return $this->spu;
    }

    public function setSpu(?Spu $spu): void
    {
        $this->spu = $spu;
    }

    public function getAutoTakeDownTime(): ?\DateTimeInterface
    {
        return $this->autoTakeDownTime;
    }

    public function setAutoTakeDownTime(?\DateTimeInterface $autoTakeDownTime): void
    {
        $this->autoTakeDownTime = $autoTakeDownTime;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isCanceled(): bool
    {
        return !$this->isActive;
    }

    public function markAsCanceled(): self
    {
        $this->isActive = false;

        return $this;
    }
}
