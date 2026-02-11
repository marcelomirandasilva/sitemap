from src.sitemap_generator.generators.xml_builder import XMLBuilder
from src.sitemap_generator.models.core import Page, ChangeFrequency

p = Page(
    url="https://efn.lt/start?do=media&ns=0",
    title="Test Page",
    content="Dummy content",
    change_frequency=ChangeFrequency.MONTHLY,
    priority=0.5
)

builder = XMLBuilder(compress_output=False)
# Ensure directory exists or use existing one. /app/sitemaps should exist
result = builder.build_main_sitemap([p], "/app/sitemaps/test_repro.xml")

with open(result['path'], 'r') as f:
    print(f.read())
