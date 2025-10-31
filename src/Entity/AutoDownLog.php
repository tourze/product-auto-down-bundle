<?php

namespace Tourze\ProductAutoDownBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\CreatedFromIpAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;
use Tourze\ProductAutoDownBundle\Repository\AutoDownLogRepository;
use Tourze\ProductCoreBundle\Entity\Spu;

#[ORM\Entity(repositoryClass: AutoDownLogRepository::class, readOnly: true)]
#[ORM\Table(name: 'product_auto_down_log', options: ['comment' => '商品自动下架日志'])]
class AutoDownLog implements \Stringable
{
    use CreateTimeAware;
    use BlameableAware;
    use SnowflakeKeyAware;
    use CreatedFromIpAware;

    #[Groups(groups: ['restful_read'])]
    #[Assert\NotNull(message: 'SPU ID不能为空')]
    #[IndexColumn]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'SPU ID'])]
    private ?int $spuId = null;

    #[Assert\NotNull(message: '配置ID不能为空')]
    #[ORM\ManyToOne(targetEntity: AutoDownTimeConfig::class)]
    #[ORM\JoinColumn(name: 'config_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?AutoDownTimeConfig $config = null;

    #[Groups(groups: ['restful_read'])]
    #[Assert\NotNull(message: '操作动作不能为空')]
    #[Assert\Choice(callback: [AutoDownLogAction::class, 'cases'], message: '请选择正确的操作动作')]
    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: AutoDownLogAction::class, options: ['comment' => '操作动作'])]
    private ?AutoDownLogAction $action = null;

    #[Groups(groups: ['restful_read'])]
    #[Assert\Length(max: 65535, maxMessage: '描述信息不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述信息'])]
    private ?string $description = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['restful_read'])]
    #[Assert\Type(type: 'array', message: '上下文信息必须为数组类型')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '上下文信息'])]
    private ?array $context = null;

    public function __toString(): string
    {
        return sprintf('SPU-%d %s',
            $this->spuId ?? 0,
            $this->action?->getLabel() ?? ''
        );
    }

    public function getSpuId(): ?int
    {
        return $this->spuId;
    }

    public function setSpuId(?int $spuId): void
    {
        $this->spuId = $spuId;
    }

    public function getConfig(): ?AutoDownTimeConfig
    {
        return $this->config;
    }

    public function setConfig(?AutoDownTimeConfig $config): void
    {
        $this->config = $config;
    }

    public function getAction(): ?AutoDownLogAction
    {
        return $this->action;
    }

    public function setAction(?AutoDownLogAction $action): void
    {
        $this->action = $action;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed>|null $context
     */
    public function setContext(?array $context): void
    {
        $this->context = $context;
    }
}
