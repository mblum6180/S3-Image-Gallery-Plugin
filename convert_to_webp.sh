#!/bin/bash

# Check if input directory is provided
if [ -z "$1" ]; then
    echo "Usage: $0 <input_directory>"
    exit 1
fi

INPUT_DIR="$1"
OUTPUT_DIR="./output"
QUALITY=80

# Ensure required tools are installed
if ! command -v convert &> /dev/null; then
    echo "Error: ImageMagick (convert) is not installed."
    exit 1
fi

# Create output directories
mkdir -p  "$OUTPUT_DIR/hires"

# Convert images
for img in "$INPUT_DIR"/*.{jpg,jpeg,png}; do
    if [ ! -f "$img" ]; then
        continue
    fi

    # Extract filename and sanitize it
    filename=$(basename -- "$img")
    filename_noext="${filename%.*}"

    # Remove special characters (keep only letters, numbers, and dashes)
    filename_clean=$(echo "$filename_noext" | sed 's/[^a-zA-Z0-9-]//g')

    # Convert to WebP with different sizes
    convert "$img" -resize 720 -quality $QUALITY "$OUTPUT_DIR/${filename_clean}.webp"
    convert "$img" -resize 960 -quality $QUALITY "$OUTPUT_DIR/hires/${filename_clean}_960.webp"
    convert "$img" -resize 1440 -quality $QUALITY "$OUTPUT_DIR/hires/${filename_clean}_1440.webp"
    convert "$img" -resize 1920 -quality $QUALITY "$OUTPUT_DIR/hires/${filename_clean}_1920.webp"

    echo "Converted: $filename → ${filename_clean}.webp"
done

echo "Conversion complete. Images saved in $OUTPUT_DIR."

