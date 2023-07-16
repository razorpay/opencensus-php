interface Item {
  id: number;
  variant_price: number;
  variant_id: string;
  name: string;
  variant_name: string;
  currentQuantity: number;
  image_url: string;
  quantity: number;
}

function generateRandomLineItems() {
  const colors: string[] = ['Red', 'Blue', 'Green', 'Yellow', 'Black', 'White'];
  const sizes: string[] = ['XS', 'S', 'M', 'L', 'XL'];
  const names: string[] = ['T-Shirt', 'Jeans', 'Dress', 'Sweater', 'Blouse'];
  const images: string[] = [
    'https://cdn.pixabay.com/photo/2019/01/09/14/13/leaves-3923413_1280.jpg',
    'https://cdn.pixabay.com/photo/2017/03/31/15/34/cactus-2191647_1280.jpg',
    'https://cdn.pixabay.com/photo/2016/06/24/15/48/pattern-1477380_1280.png',
    'https://cdn.pixabay.com/photo/2019/05/05/21/42/doodle-4181783_1280.png',
    'https://cdn.pixabay.com/photo/2015/09/13/05/58/lighthouse-937738_1280.jpg',
    'https://cdn.pixabay.com/photo/2019/04/21/21/32/nature-4145029_1280.jpg',
    'https://cdn.pixabay.com/photo/2018/12/25/11/10/deer-3894103_1280.png',
  ];

  const randomColor: string = colors[Math.floor(Math.random() * colors.length)];
  const randomSize: string = sizes[Math.floor(Math.random() * sizes.length)];
  const randomName: string = names[Math.floor(Math.random() * names.length)];
  const randomImage: string = images[Math.floor(Math.random() * images.length)];

  const clothItem = {
    name: `${randomName}`,
    image: randomImage,
    variant_name: `${randomColor} / ${randomSize}`,
  };

  return clothItem;
}

export function generateSampleData(count: number): Item[] {
  const sampleData: any[] = [];

  for (let i = 0; i < count; i++) {
    const clothItem = generateRandomLineItems();
    const newItem = {
      quantity: Math.floor(Math.random() * 10),
      currentQuantity: Math.floor(Math.random() * 100),
      image_url: clothItem.image,
      variant_name: clothItem.variant_name,
      name: clothItem.name,
      variant_id: `gid://shopify/LineItem/${Math.floor(Math.random() * 10000)}`,
      variant_price: Math.floor(Math.random() * 1000),
      id: Math.floor(Math.random() * 1000000000),
    };

    sampleData.push(newItem);
  }
  return sampleData;
}
